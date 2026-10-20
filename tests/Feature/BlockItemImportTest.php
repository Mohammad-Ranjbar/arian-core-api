<?php

namespace Tests\Feature;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Imports\BlockItemImport;
use App\Models\BlockItem;
use App\Models\Job;
use App\Models\RestErpJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class BlockItemImportTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        Auth::forgetGuards();
        config()->set('rest_erp.base_url', 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0');
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
        Event::fake([JobProcessEvent::class]);
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->unsignedBigInteger('car_model_id')->nullable();
            $table->unsignedBigInteger('make_code_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('block_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('block_type');
            $table->softDeletes();
        });
        Schema::create('block_items', function (Blueprint $table) {
            $table->id();
            $table->string('vin');
            $table->unsignedBigInteger('blocked_by');
            $table->unsignedBigInteger('block_reason_id');
            $table->string('description');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('custom_jobs', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->integer('user_id');
            $table->json('result')->nullable();
            $table->timestamps();
        });
        DB::table('block_reasons')->insert(['id' => 7, 'block_type' => 'cop']);
        DB::table('vehicles')->insert([['vin' => 'VIN-FAIL'], ['vin' => 'VIN-OK']]);
    }

    public static function failures(): array
    {
        return [
            'business error' => [400, ['error' => ['code' => 'Application_DialogException', 'message' => 'Blocking rejected.']], 'failed', 'Blocking rejected.'],
            'authentication' => [401, [], 'failed', 'کد وضعیت HTTP 401'],
            'server error' => [500, [], 'uncertain', 'کد وضعیت HTTP 500'],
            'timeout' => [0, [], 'uncertain', 'Connection timed out.'],
        ];
    }

    #[DataProvider('failures')]
    public function test_failed_row_is_not_saved_and_import_continues_with_next_row(int $status, array $body, string $jobStatus, string $message): void
    {
        $attempts = 0;
        Http::fake(function (Request $request) use ($status, $body, &$attempts) {
            $attempts++;
            $this->assertDatabaseMissing('block_items', ['vin' => $request['BarcodeInfo']]);
            if ($request['BarcodeInfo'] === 'VIN-FAIL') {
                if ($status === 0) {
                    throw new ConnectionException('Connection timed out.');
                }

                return Http::response($body, $status);
            }

            return Http::response(['accepted' => true], 201);
        });
        $job = Job::query()->create(['user_id' => 43]);
        $import = new BlockItemImport([
            'id' => $job->getKey(), 'user_id' => 43, 'done' => 0, 'success' => 0, 'error' => 0,
        ], 'Imported block', '7', '43');
        $failed = collect(['VIN-FAIL']);
        $success = collect(['VIN-OK']);
        $missing = collect(['VIN-MISSING']);
        Excel::shouldReceive('store')->once()->withArgs(function (ResultExport $export, string $path) use ($failed, $success, $missing, $message) {
            $this->assertSame(BlockItem::HEADING, $export->collection()->first());
            $this->assertStringContainsString($message, $failed['result']);
            $this->assertSame('imported', $success['result']);
            $this->assertSame('not imported', $missing['result']);
            $this->assertStringStartsWith('public/results/', $path);

            return true;
        })->andReturn(true);

        $import->collection(collect([$failed, $success, $missing]));

        $this->assertDatabaseCount('block_items', 1);
        $this->assertDatabaseHas('block_items', ['vin' => 'VIN-OK', 'blocked_by' => 43, 'block_reason_id' => 7]);
        $this->assertDatabaseCount('erp_jobs', 0);
        $jobs = RestErpJob::query()->orderBy('id')->get();
        $this->assertCount(2, $jobs);
        $this->assertSame([$jobStatus, 'success'], $jobs->pluck('status')->all());
        foreach ($jobs as $erpJob) {
            $this->assertSame(43, $erpJob->user_id);
            $this->assertSame('sync', $erpJob->execution_mode);
            $this->assertTrue($erpJob->actionLogs()->where('action', 'rest_erp_'.$erpJob->status)->exists());
        }
        $this->assertSame([3, 3, 1, 2], array_map(fn ($key) => $import->data[$key], ['total', 'done', 'success', 'error']));
        $this->assertSame(1, $job->refresh()->result['success']);
        $this->assertSame(2, $job->result['error']);
        $this->assertSame(2, $attempts);
        Http::assertSentCount($status === 0 ? 1 : 2);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === config('rest_erp.base_url').'/VehicleTypeBlockings'
            && $request->data() === ['BarcodeInfo' => 'VIN-OK', 'BlockType' => 'cop']);
        Queue::assertNothingPushed();
        Event::assertDispatchedTimes(JobProcessEvent::class, 4);
    }
}
