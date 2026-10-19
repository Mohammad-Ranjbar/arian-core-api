<?php

namespace Tests\Feature;

use App\Models\RestErpJob;
use App\Services\Erp\RestApi\RestErpJobService;
use App\Services\Erp\RestApi\ReturnRequestsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class ReturnRequestsSyncTest extends TestCase
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

    public static function outcomes(): array
    {
        return [
            'success' => [[], 200, 'success'],
            'business error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Return refused.']], 400, 'failed'],
            'server error' => [['error' => ['code' => 'Internal_ServerError', 'message' => 'Unavailable.']], 500, 'uncertain'],
            'connection error' => [null, 0, 'uncertain'],
        ];
    }

    #[DataProvider('outcomes')]
    public function test_resubmissions_retry_errors_on_the_same_job_and_reuse_successes(mixed $body, int $status, string $jobStatus): void
    {
        $attempts = 0;
        Http::fake(function () use ($body, $status, &$attempts) {
            $attempts++;
            // Registration must be durable before the remote operation starts.
            $this->assertSame(0, DB::transactionLevel());
            $this->assertSame('in-progress', RestErpJob::query()->sole()->status);
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $status);
        });
        $service = resolve(ReturnRequestsService::class);
        $first = $service->sendOrRetry('VIN-1', 'DESTINATION', 2);
        $job = RestErpJob::query()->sole();
        $before = $job->getAttributes();

        $this->assertSame($first, $service->sendOrRetry('VIN-1', 'DESTINATION', '2'));
        $job->refresh();
        $expectedAttempts = $jobStatus === 'success' ? 1 : 2;
        $this->assertSame($before['id'], $job->id);
        $this->assertSame($before['uuid'], $job->uuid);
        $this->assertSame($expectedAttempts, $attempts);
        $this->assertSame($expectedAttempts, $job->tries);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame($jobStatus, $job->status);
        $this->assertSame(['rest_erp_created', ...array_fill(0, $expectedAttempts, 'rest_erp_'.$jobStatus)], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        Queue::assertNothingPushed();
    }

    public function test_a_repeated_submission_during_the_http_call_does_not_execute_again(): void
    {
        Http::fake(function () {
            $duplicate = resolve(ReturnRequestsService::class)->sendOrRetry('VIN-1', 'DESTINATION', 2);
            $this->assertFalse($duplicate['success']);
            $this->assertSame(409, $duplicate['http_status']);
            $this->assertSame('ERP_JOB_IN_PROGRESS', $duplicate['error']['code']);

            return Http::response([], 200);
        });

        $this->assertTrue(resolve(ReturnRequestsService::class)->sendOrRetry('VIN-1', 'DESTINATION', 2)['success']);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
    }

    public function test_a_previous_linked_queue_job_is_returned_without_dispatching_or_retrying_it(): void
    {
        $queued = resolve(RestErpJobService::class)->enqueue('ReturnRequests', [
            'VIN' => 'VIN-1', 'Destination' => 'DESTINATION', 'ItemType' => '2',
        ], Auth::user());
        $before = RestErpJob::query()->sole()->getAttributes();

        $result = resolve(ReturnRequestsService::class)->sendOrRetry('VIN-1', 'DESTINATION', 2);

        $this->assertSame($queued['data']['rest_erp_job_id'], $result['rest_erp_job_id']);
        $this->assertSame(409, $result['http_status']);
        $this->assertSame($before, RestErpJob::query()->sole()->getAttributes());
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        Http::assertNothingSent();
    }

    public function test_changing_the_destination_creates_a_different_request_and_old_results_still_match(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $service = resolve(ReturnRequestsService::class);
        $first = $service->sendOrRetry('VIN-1', 'DESTINATION-A', 2);
        $second = $service->sendOrRetry('VIN-1', 'DESTINATION-B', 2);

        $this->assertNotSame($first['rest_erp_job_id'], $second['rest_erp_job_id']);
        $this->assertSame($first, $service->sendOrRetry('VIN-1', 'DESTINATION-A', 2));
        $this->assertDatabaseCount('rest_erp_jobs', 2);
        Http::assertSentCount(2);
    }

    public function test_an_ignored_validation_failure_does_not_create_another_job(): void
    {
        $service = resolve(ReturnRequestsService::class);
        $first = $service->sendOrRetry('VIN-1', '', 2);
        $job = RestErpJob::query()->sole();
        $this->assertSame('rejected', $job->status);
        resolve(RestErpJobService::class)->ignore($job, 42);

        $this->assertSame($first, $service->sendOrRetry('VIN-1', '', 2));
        $this->assertSame('ignored', $job->fresh()->status);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        $this->assertSame(['rest_erp_created'], $job->actionLogs()->pluck('action')->all());
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }
}
