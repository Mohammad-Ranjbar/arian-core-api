<?php

namespace Tests\Feature;

use App\Jobs\ExportJob;
use App\Jobs\ImportJob;
use App\Jobs\PrintEngineLableJob;
use App\Jobs\PrintSynchronousJob;
use App\Jobs\SmsJob;
use App\Models\Job;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\SyncQueue;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Horizon\ProvisioningPlan;
use Tests\TestCase;

class QueueWorkloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('custom_jobs', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->json('data')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function test_horizon_plan_has_independent_capacity_and_safe_reservation_times(): void
    {
        $plan = new ProvisioningPlan('test', config('horizon.environments'), config('horizon.defaults'));

        foreach ($plan->environments() as $environment) {
            $queues = [];
            foreach ($plan->parsed[$environment] as $options) {
                $this->assertContains($options->balance, ['auto', 'simple', false]);
                $this->assertGreaterThanOrEqual(1, $options->minProcesses);
                $this->assertGreaterThanOrEqual($options->minProcesses, $options->maxProcesses);
                $this->assertLessThan(config('queue.connections.redis.retry_after'), $options->timeout);
                $this->assertSame(1, $options->maxTries);
                $queues[] = $options->queue;
            }
            $this->assertEqualsCanonicalizing(['default', 'sms', 'printing', 'exports', 'imports', 'unique', 'parallel'], $queues);
            $this->assertSame(1, $plan->optionsFor($environment, 'supervisor-unique')->maxProcesses);
            $this->assertSame(1, $plan->optionsFor($environment, 'supervisor-printing')->maxProcesses);
        }

        $this->assertGreaterThan(1, $plan->optionsFor('production', 'supervisor-default')->maxProcesses);
        $this->assertGreaterThan(1, $plan->optionsFor('production', 'supervisor-sms')->maxProcesses);
    }

    public function test_jobs_route_through_the_bus_without_changing_call_sites(): void
    {
        $payloadQueue = new class extends SyncQueue
        {
            public function payloadFor(object $job, string $queue): array
            {
                return json_decode($this->createPayload($job, $queue), true);
            }
        };
        $payloadQueue->setContainer($this->app);
        Queue::fake();
        $jobs = [
            [new ExportJob('export-id'), 'exports'],
            [new ImportJob('import-id'), 'imports'],
            [new SmsJob('09120000000', 'test'), 'sms'],
            [new PrintSynchronousJob(1), 'printing'],
            [new PrintEngineLableJob('engine', 'vin', '127.0.0.1', '1'), 'printing'],
        ];

        foreach ($jobs as [$job, $queue]) {
            dispatch($job);
            Queue::assertPushedOn($queue, $job::class);
            $payload = $payloadQueue->payloadFor($job, $queue);
            $this->assertSame(1, $payload['maxTries']);
            $this->assertTrue($payload['failOnTimeout']);
            $this->assertLessThan(config('queue.connections.redis.retry_after'), $payload['timeout']);
        }
    }

    public function test_failed_export_and_import_update_the_record_through_laravel_failure_handling(): void
    {
        foreach ([ExportJob::class, ImportJob::class] as $class) {
            $record = Job::create(['type' => 'invalid', 'data' => []]);

            try {
                Queue::connection('sync')->push(new $class($record->uuid));
                $this->fail('The invalid workload must fail.');
            } catch (\Exception $exception) {
                $this->assertContains($exception->getMessage(), ['not found importer', 'Invalid input for type: invalid']);
            }

            $record->refresh();
            $this->assertSame('failed', $record->status);
            $this->assertArrayHasKey('message', $record->result);
            $this->assertStringNotContainsString('invalid', $record->result['message']);
        }
    }

    public function test_timeout_callback_preserves_finished_records_and_handles_deleted_records(): void
    {
        foreach ([ExportJob::class, ImportJob::class] as $class) {
            $record = Job::create(['type' => 'test', 'status' => 'started']);
            $job = new $class($record->uuid);
            $job->failed(new TimeoutExceededException);
            $this->assertSame('failed', $record->fresh()->status);

            $record->update(['status' => 'finished', 'result' => ['file' => 'existing.xlsx']]);
            $job->handle();
            $job->failed(new TimeoutExceededException);
            $this->assertSame('finished', $record->fresh()->status);
            $this->assertSame(['file' => 'existing.xlsx'], $record->fresh()->result);

            $record->delete();
            $job->failed(new TimeoutExceededException);
            $this->assertNull(Job::find($record->uuid));
        }
    }

    public function test_sms_request_is_bounded_and_keeps_the_provider_payload(): void
    {
        config(['sms.tsms.username' => 'test-user', 'sms.tsms.password' => 'test-pass', 'sms.tsms.from' => '1000']);
        Http::preventStrayRequests();
        Http::fake(function ($request, $options) {
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(20, $options['timeout']);
            $this->assertSame([
                'method' => 'sendSms',
                'username' => 'test-user',
                'password' => 'test-pass',
                'sms_number' => ['1000'],
                'mobile' => ['09120000000'],
                'msg' => ['test'],
            ], $request->data());

            return Http::response(['result' => 'ok']);
        });

        (new SmsJob('09120000000', ' test '))->handle();
        Http::assertSentCount(1);
    }

    public function test_sms_http_failure_is_not_reported_as_success_or_retried(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 503)]);

        try {
            (new SmsJob('09120000000', 'test'))->handle();
            $this->fail('HTTP failure must reach the queue.');
        } catch (RequestException $exception) {
            $this->assertSame(503, $exception->response->status());
        }

        Http::assertSentCount(1);
    }
}
