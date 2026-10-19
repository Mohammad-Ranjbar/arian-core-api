<?php

namespace Tests\Feature;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Http\Controllers\BlockItemController;
use App\Http\Requests\UnblockItemRequest;
use App\Http\Resources\BlockItemResource;
use App\Imports\UnblockItemImport;
use App\Models\BlockItem;
use App\Models\Job;
use App\Models\RestErpJob;
use App\Models\Vehicle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class VehicleUnblocksTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('rest_erp.base_url', 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0');
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
        Gate::before(fn () => true);
        Event::fake([JobProcessEvent::class]);
        Schema::create('block_items', function (Blueprint $table) {
            $table->id();
            $table->string('vin');
            $table->unsignedBigInteger('blocked_by');
            $table->unsignedBigInteger('unblocked_by')->nullable();
            $table->timestamp('unblocked_at')->nullable();
            $table->string('unblock_description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('custom_jobs', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->integer('user_id');
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    private function block(string $vin = 'JE4LSW148TZ700303'): BlockItem
    {
        return BlockItem::query()->create(['vin' => $vin, 'blocked_by' => 42]);
    }

    public static function outcomes(): array
    {
        return [
            'success' => [201, 'success', 201],
            'business' => [400, 'failed', 422],
            'auth' => [401, 'failed', 502],
            'server' => [500, 'uncertain', 502],
            'timeout' => [0, 'uncertain', 503],
        ];
    }

    private function fakeResponse(BlockItem $block, int $status): void
    {
        Http::fake(function (Request $request, array $options) use ($block, $status) {
            $this->assertNull($block->fresh()->unblocked_at);
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);
            $this->assertSame('POST', $request->method());
            $this->assertSame(config('rest_erp.base_url').'/VehicleUnblocks', $request->url());
            $this->assertSame(['BarcodeInfo' => $block->vin], $request->data());
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($status === 400
                ? ['error' => ['code' => 'Application_DialogException', 'message' => 'Unblock rejected.']]
                : [], $status);
        });
    }

    private function assertJob(BlockItem $block, string $status, int $actor): void
    {
        $job = RestErpJob::query()->sole();
        $this->assertSame('VehicleUnblocks', $job->endpoint);
        $this->assertSame($status, $job->status);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame($actor, $job->user_id);
        $this->assertSame(BlockItem::class, $job->model_type);
        $this->assertSame((string) $block->id, $job->model_id);
        $this->assertTrue($job->actionLogs()->where('action', 'rest_erp_'.$status)->exists());
        $this->assertDatabaseCount('erp_jobs', 0);
        Queue::assertNothingPushed();
    }

    #[DataProvider('outcomes')]
    public function test_controller_changes_local_state_only_on_success(int $httpStatus, string $status, int $expectedStatus): void
    {
        $block = $this->block();
        $this->fakeResponse($block, $httpStatus);
        $request = UnblockItemRequest::create('/', 'POST', ['unblock_description' => 'Approved']);
        $response = resolve(BlockItemController::class)->unblock(new Vehicle(['vin' => $block->vin]), $block->id, $request);

        if ($status === 'success') {
            $this->assertInstanceOf(BlockItemResource::class, $response);
            $this->assertNotNull($block->fresh()->unblocked_at);
            $this->assertSame(42, $block->fresh()->unblocked_by);
            $this->assertSame('Approved', $block->fresh()->unblock_description);
        } else {
            $this->assertSame($expectedStatus, $response->status());
            $this->assertNotEmpty($response->getData(true)['message']);
            $this->assertNull($block->fresh()->unblocked_at);
            $this->assertNull($block->fresh()->unblocked_by);
        }
        $this->assertJob($block, $status, 42);
    }

    #[DataProvider('outcomes')]
    public function test_import_keeps_failed_row_blocked_and_continues(int $httpStatus, string $status, int $expectedStatus): void
    {
        $block = $this->block();
        $this->fakeResponse($block, $httpStatus);
        $row = collect([$block->id]);
        $job = Job::query()->create(['user_id' => 43]);
        $import = new UnblockItemImport([
            'id' => $job->getKey(), 'user_id' => 43, 'done' => 0, 'success' => 0, 'error' => 0,
        ]);
        Excel::shouldReceive('store')->once()->withArgs(fn (ResultExport $export, string $path) => $export->collection()->count() === 3 && str_starts_with($path, 'public/results/'))->andReturn(true);

        $import->collection(collect([$row, collect([999])]));

        if ($status === 'success') {
            $this->assertNotNull($block->fresh()->unblocked_at);
            $this->assertSame(43, $block->fresh()->unblocked_by);
            $this->assertSame('imported', $row['result']);
        } else {
            $this->assertNull($block->fresh()->unblocked_at);
            $this->assertNull($block->fresh()->unblocked_by);
            $this->assertNotSame('imported', $row['result']);
            $this->assertNotEmpty($row['result']);
        }
        $this->assertSame(2, $import->data['done']);
        $this->assertSame($status === 'success' ? 1 : 0, $job->refresh()->result['success']);
        $this->assertSame($status === 'success' ? 1 : 2, $job->result['error']);
        $this->assertJob($block, $status, 43);
        Event::assertDispatchedTimes(JobProcessEvent::class, 3);
    }

    public function test_import_contacts_erp_only_when_removing_the_last_active_block(): void
    {
        $first = $this->block();
        $last = $this->block();
        $this->fakeResponse($last, 201);
        $job = Job::query()->create(['user_id' => 43]);
        $import = new UnblockItemImport([
            'id' => $job->getKey(), 'user_id' => 43, 'done' => 0, 'success' => 0, 'error' => 0,
        ]);
        Excel::shouldReceive('store')->once()->andReturn(true);

        $import->collection(collect([collect([$first->id]), collect([$last->id])]));

        Http::assertSentCount(1);
        $this->assertNotNull($first->fresh()->unblocked_at);
        $this->assertNotNull($last->fresh()->unblocked_at);
        $this->assertSame(2, $import->data['success']);
        $this->assertJob($last, 'success', 43);
    }

    public function test_controller_does_not_contact_erp_for_another_vehicles_block(): void
    {
        $this->block();
        $other = $this->block('OTHER-VIN');
        Http::fake();

        $response = resolve(BlockItemController::class)->unblock(
            new Vehicle(['vin' => 'JE4LSW148TZ700303']), $other->id, UnblockItemRequest::create('/'),
        );

        $this->assertSame(404, $response->status());
        $this->assertSame('رکورد مسدودی مورد نظر برای این خودرو یافت نشد.', $response->getData(true)['message']);
        Http::assertNothingSent();
        $this->assertNull($other->fresh()->unblocked_at);
    }

    public function test_controller_returns_clear_error_for_already_unblocked_record(): void
    {
        $block = $this->block();
        $block->update(['unblocked_at' => now(), 'unblocked_by' => 43]);
        Http::fake();

        $response = resolve(BlockItemController::class)->unblock(
            new Vehicle(['vin' => $block->vin]), $block->id, UnblockItemRequest::create('/'),
        );

        $this->assertSame(422, $response->status());
        $this->assertSame('این رکورد قبلاً رفع مسدودی شده است.', $response->getData(true)['message']);
        $this->assertSame(43, $block->fresh()->unblocked_by);
        Http::assertNothingSent();
    }

    public function test_controller_returns_clear_error_for_missing_record(): void
    {
        Http::fake();

        $response = resolve(BlockItemController::class)->unblock(
            new Vehicle(['vin' => 'JE4LSW148TZ700303']), 999, UnblockItemRequest::create('/'),
        );

        $this->assertSame(404, $response->status());
        $this->assertSame('رکورد مسدودی مورد نظر برای این خودرو یافت نشد.', $response->getData(true)['message']);
        Http::assertNothingSent();
    }
}
