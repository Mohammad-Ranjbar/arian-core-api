<?php

namespace Tests\Feature;

use App\Http\Controllers\VehicleAllocationController;
use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Models\User;
use App\Models\VehicleAllocation;
use App\Services\Erp\RestApi\ProductionPostingBINsService;
use App\Services\Erp\RestApi\RestErpJobService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class VehicleAllocationSendTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config(['rest_erp.base_url' => 'http://erp.test/api/arianCapital', 'erp.auth' => ['user', 'password', 'ntlm']]);
        Http::preventStrayRequests();
        Queue::fake();
        Schema::create('vehicle_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('vin');
            $table->boolean('is_complete')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('erp_response')->nullable();
            $table->timestamps();
        });
    }

    private function allocation(): VehicleAllocation
    {
        return VehicleAllocation::create([
            'vin' => 'N5RL40000S0002132', 'user_id' => 43,
            'erp_response' => ['old' => 'response'],
        ]);
    }

    private function submit(VehicleAllocation $allocation): array
    {
        return resolve(VehicleAllocationController::class)
            ->send($allocation, Request::create('/api/vehicle-allocations/'.$allocation->id, 'POST'))
            ->getData(true);
    }

    public static function outcomes(): array
    {
        return [
            'success' => [['value' => 'Posted'], 201, true],
            'business error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Refused']], 400, false],
            'server error' => [['error' => ['code' => 'InternalError', 'message' => 'Unavailable']], 500, false],
        ];
    }

    #[DataProvider('outcomes')]
    public function test_submission_clears_the_previous_response_and_saves_the_final_result(array $body, int $status, bool $success): void
    {
        $allocation = $this->allocation();
        $accepted = $this->submit($allocation);
        $this->assertSame(202, $accepted['http_status']);
        $this->assertSame(42, $allocation->fresh()->user_id);
        $this->assertNull($allocation->fresh()->erp_response);
        Http::assertNothingSent();
        Queue::assertPushed(SendToRestErpJob::class, fn ($job) => $job->queue === 'unique');
        Http::fake(function ($request) use ($body, $status, $allocation) {
            $this->assertSame([
                'VIN' => $allocation->vin, 'WCGroup' => '', 'WithTracking' => 'false',
                'WithOutput' => 'false', 'ItemType' => '2',
            ], $request->data());
            $this->assertNull($allocation->fresh()->erp_response);

            return Http::response($body, $status);
        });
        $result = resolve(RestErpJobService::class)->execute($accepted['data']['rest_erp_job_id']);
        $this->assertSame($success, $result['success']);
        $this->assertSame($result, $allocation->fresh()->erp_response);
    }

    public function test_endpoint_returns_202_with_job_identifiers(): void
    {
        $allocation = $this->allocation();
        $response = $this->postJson('/api/vehicle-allocations/'.$allocation->id)->assertStatus(202);
        $job = RestErpJob::query()->sole();
        $response->assertJsonPath('data.rest_erp_job_id', $job->id)
            ->assertJsonPath('data.request_id', $job->uuid);
        $this->assertSame($allocation->getMorphClass(), $job->model_type);
        $this->assertSame((string) $allocation->id, (string) $job->model_id);
        $this->assertSame(42, $allocation->fresh()->user_id);
        $this->assertNull($allocation->fresh()->erp_response);
        Http::assertNothingSent();
    }

    public function test_connection_error_is_saved_and_retry_clears_it_and_updates_the_actor(): void
    {
        $allocation = $this->allocation();
        $accepted = $this->submit($allocation);
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));
        $jobs = resolve(RestErpJobService::class);
        $jobs->execute($accepted['data']['rest_erp_job_id']);
        $this->assertSame('ERP_CONNECTION_ERROR', $allocation->fresh()->erp_response['error']['code']);
        $jobs->retry(RestErpJob::findOrFail($accepted['data']['rest_erp_job_id']), 43, acknowledgeUncertain: true);
        $this->assertNull($allocation->fresh()->erp_response);
        $this->assertSame(43, $allocation->fresh()->user_id);
    }

    public function test_repeated_pending_submission_does_not_replace_the_original_actor(): void
    {
        $allocation = $this->allocation();
        $first = $this->submit($allocation);
        $this->actingAs(User::findOrFail(43));
        $this->assertSame($first['data'], $this->submit($allocation)['data']);
        $this->assertSame(42, $allocation->fresh()->user_id);
        Queue::assertPushed(SendToRestErpJob::class, 1);
    }

    public function test_local_validation_failure_is_saved_without_dispatching(): void
    {
        $allocation = $this->allocation();
        $result = resolve(ProductionPostingBINsService::class)
            ->send($allocation->vin, '', 'false', 'true', '', model: $allocation)->unique();

        $this->assertSame(422, $result['http_status']);
        $this->assertSame($result, $allocation->fresh()->erp_response);
        $this->assertSame(42, $allocation->fresh()->user_id);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_submission_rolls_back_with_the_transaction(): void
    {
        $allocation = $this->allocation();
        DB::beginTransaction();
        $this->submit($allocation);
        $this->assertNull($allocation->fresh()->erp_response);
        DB::rollBack();

        $this->assertSame(['old' => 'response'], $allocation->fresh()->erp_response);
        $this->assertSame(43, $allocation->fresh()->user_id);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
    }

    public function test_queue_failure_is_saved(): void
    {
        $allocation = $this->allocation();
        $accepted = $this->submit($allocation);
        resolve(RestErpJobService::class)->fail($accepted['data']['rest_erp_job_id'], new \RuntimeException('Worker stopped'));
        $this->assertSame('ERP_QUEUE_ERROR', $allocation->fresh()->erp_response['error']['code']);
    }
}
