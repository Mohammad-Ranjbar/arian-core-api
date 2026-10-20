<?php

namespace Tests\Feature;

use App\Exceptions\RestErpQueueException;
use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\RestErpExecutionMode;
use App\Services\Erp\RestApi\TrackingsService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class PendingRestErpRequestTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
    }

    public static function serviceModes(): array
    {
        return RestErpExecutionModeTest::serviceModes();
    }

    #[DataProvider('serviceModes')]
    public function test_send_is_deferred_and_the_terminal_method_selects_the_mode(string $service, string $endpoint, array $arguments, RestErpExecutionMode $mode): void
    {
        // Explicit fluent methods must override even the opposite configured default.
        config()->set('rest_erp.endpoints.'.$endpoint.'.mode', $mode === RestErpExecutionMode::Sync ? 'unique' : 'sync');
        Http::fake(['*' => Http::response(['accepted' => true], 201)]);

        $request = resolve($service)->send(...$arguments);

        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
        $terminal = $mode->value;
        $result = $request->$terminal();

        $job = RestErpJob::query()->sole();
        $this->assertTrue($result['success']);
        $this->assertSame($endpoint, $job->endpoint);
        if ($mode === RestErpExecutionMode::Sync) {
            $this->assertSame(['accepted' => true], $result['data']);
            $this->assertSame(201, $result['http_status']);
            $this->assertSame('sync', $job->execution_mode);
            $this->assertSame('success', $job->status);
            $this->assertSame(1, $job->tries);
            Http::assertSentCount(1);
            Queue::assertNothingPushed();
        } else {
            $this->assertSame(202, $result['http_status']);
            $this->assertSame($job->id, $result['data']['rest_erp_job_id']);
            $this->assertSame($mode->value, $job->queue);
            $this->assertSame('pending', $job->status);
            $this->assertSame(0, $job->tries);
            Queue::assertPushedOn($mode->value, SendToRestErpJob::class);
            Http::assertNothingSent();
        }
        // Reusing the same prepared object must not repeat the external side effect.
        $this->assertSame($result, $request->$terminal());
        $this->assertDatabaseCount('rest_erp_jobs', 1);
    }

    public function test_an_executed_request_cannot_be_sent_in_another_mode(): void
    {
        $request = resolve(TrackingsService::class)->send('VIN-1', 'BARCODE-1', 2);
        $request->parallel();
        try {
            $request->unique();
            $this->fail('An executed request cannot change modes.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('قبلاً در حالت', $exception->getMessage());
        }
        Queue::assertPushed(SendToRestErpJob::class, 1);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
    }

    public function test_fluent_queue_dispatch_waits_for_commit(): void
    {
        DB::beginTransaction();
        $result = resolve(TrackingsService::class)->send('VIN-1', 'BARCODE-1', 2)->unique();
        $this->assertSame(202, $result['http_status']);
        Queue::assertNothingPushed();
        DB::commit();

        Queue::assertPushedOn('unique', SendToRestErpJob::class);
        Http::assertNothingSent();
    }

    public function test_a_broker_exception_is_preserved_without_attempting_another_dispatch(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Broker unavailable.'));
        $request = resolve(TrackingsService::class)->send('VIN-1', 'BARCODE-1', 2);
        $failure = null;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $request->parallel();
                $this->fail('The broker failure must be returned.');
            } catch (RestErpQueueException $exception) {
                if ($failure !== null) {
                    $this->assertSame($failure, $exception);
                }
                $failure = $exception;
            }
        }
        $this->assertSame('failed', RestErpJob::query()->sole()->status);
        Http::assertNothingSent();
    }
}
