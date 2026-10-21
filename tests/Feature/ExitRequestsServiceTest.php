<?php

namespace Tests\Feature;

use App\Exceptions\RestErpQueueException;
use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\ExitRequestsService;
use App\Services\Erp\RestApi\RestErpJobService;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class ExitRequestsServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/ExitRequests';

    private const PAYLOAD = ['VIN' => 'N5RT92CF6SP000481', 'TypeOfExit' => '1', 'Destination' => 'CT004156', 'ItemType' => '2'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Queue::fake();
        Http::preventStrayRequests();
    }

    private function enqueue(array $overrides = []): array
    {
        return resolve(ExitRequestsService::class)->queue(...array_replace([
            'vin' => self::PAYLOAD['VIN'], 'typeOfExit' => 1, 'destination' => 'CT004156', 'itemType' => 2,
        ], $overrides));
    }

    public function test_it_queues_the_exact_payload_on_parallel_without_http(): void
    {
        $result = $this->enqueue();
        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertSame(202, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame($job->id, $result['data']['rest_erp_job_id']);
        $this->assertSame($job->uuid, $result['data']['request_id']);
        $this->assertSame('pending', $job->status);
        $this->assertSame('queue', $job->execution_mode);
        $this->assertSame('parallel', $job->queue);
        $this->assertSame('redis', $job->connection);
        $this->assertSame(0, $job->tries);
        $this->assertSame(ExitRequestsService::class, $job->service);
        $this->assertSame(self::PAYLOAD, $job->parameters);
        $this->assertSame(self::PAYLOAD, json_decode(DB::table('rest_erp_jobs')->value('parameters'), true));
        $this->assertSame(['rest_erp_created'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        foreach ($job->actionLogs()->get() as $log) {
            $this->assertSame(self::PAYLOAD, $log->data['snapshot']['parameters']);
        }
        Queue::assertPushedOn('parallel', SendToRestErpJob::class, fn ($queued) => $queued->id === (string) $job->id && $queued->connection === 'redis');
        Queue::assertPushed(SendToRestErpJob::class, 1);
        Http::assertNothingSent();
        $this->assertDatabaseCount('erp_jobs', 0);
    }

    public function test_worker_posts_the_exact_json_using_the_shared_server_and_records_success(): void
    {
        $result = $this->enqueue();
        $body = ['VIN' => self::PAYLOAD['VIN']];
        Http::fake(function (Request $request, array $options) use ($body) {
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(300, $options['timeout']);

            return Http::response($body, 201);
        });
        $outcome = resolve(RestErpJobService::class)->execute($result['data']['rest_erp_job_id']);
        $this->assertSame(['success' => true, 'data' => $body, 'error' => null, 'http_status' => 201], $outcome);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::URL && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json') && $request->hasHeader('Accept', 'application/json')
            && $request->data() === self::PAYLOAD);
        $job = RestErpJob::query()->sole();
        $this->assertSame('success', $job->status);
        $this->assertSame(1, $job->tries);
        $this->assertSame(201, $job->provider_http_status);
        $this->assertSame(['rest_erp_created', 'rest_erp_success'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        $serialized = json_encode([$job->toArray(), $job->actionLogs()->get()->toArray()]);
        $this->assertStringNotContainsString('test-password', $serialized);
        $this->assertStringNotContainsString('test-user', $serialized);
        foreach (['client_computer_name', 'request_headers', 'client_ip', 'peer_ip', 'user_agent', 'response', 'response_headers', 'response_context'] as $removed) {
            $this->assertArrayNotHasKey($removed, $job->toArray());
        }
    }

    public function test_send_preserves_the_sync_contract_and_audit(): void
    {
        Http::fake([self::URL => Http::response([], 200)]);
        $result = resolve(ExitRequestsService::class)->send(self::PAYLOAD['VIN'], 1, 'CT004156', 2)->sync();
        $this->assertSame(['success' => true, 'data' => [], 'error' => null, 'http_status' => 200], $result);
        $this->assertSame('sync', RestErpJob::query()->sole()->execution_mode);
        Queue::assertNothingPushed();
        Http::assertSentCount(1);
    }

    public static function invalidInputs(): array
    {
        return [
            'vin' => [['vin' => '']], 'exit type' => [['typeOfExit' => '']],
            'destination' => [['destination' => '']], 'item type' => [['itemType' => '']],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_is_audited_and_never_queued(array $overrides): void
    {
        $result = $this->enqueue($overrides);
        $this->assertFalse($result['success']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame(422, $result['http_status']);
        $this->assertSame('rejected', RestErpJob::query()->sole()->status);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_validation_does_not_invent_formats_or_enums_from_the_sample(): void
    {
        $result = $this->enqueue(['vin' => 'custom-id', 'typeOfExit' => 'custom-type', 'destination' => 'custom-destination', 'itemType' => 'custom-item']);
        $this->assertTrue($result['success']);
        $this->assertSame(['VIN' => 'custom-id', 'TypeOfExit' => 'custom-type', 'Destination' => 'custom-destination', 'ItemType' => 'custom-item'], RestErpJob::query()->sole()->parameters);
    }

    public function test_dispatch_waits_for_commit_and_rollback_cancels_the_job(): void
    {
        DB::beginTransaction();
        $this->enqueue();
        Queue::assertNothingPushed();
        DB::rollBack();
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        DB::beginTransaction();
        $this->enqueue();
        Queue::assertNothingPushed();
        DB::commit();
        Queue::assertPushed(SendToRestErpJob::class, 1);
        Http::assertNothingSent();
    }

    public function test_broker_failure_preserves_the_failed_job_and_returns_503(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Redis connection refused. password=broker-secret'));
        try {
            $this->enqueue();
            $this->fail('Expected queue failure.');
        } catch (RestErpQueueException $exception) {
            $this->assertSame(503, $exception->getResponse()->getStatusCode());
            $job = RestErpJob::query()->sole();
            $this->assertSame('failed', $job->status);
            $this->assertSame('ERP_QUEUE_ERROR', $job->error_code);
            $this->assertStringNotContainsString('broker-secret', json_encode($job->actionLogs()->get()->toArray()));
        }
        Http::assertNothingSent();
    }

    public static function failures(): array
    {
        return [
            'business' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Request refused.']], 400, 422, 'failed', 'Application_DialogException'],
            'auth' => [[], 401, 502, 'failed', 'ERP_AUTH_ERROR'],
            'server' => [[], 500, 502, 'uncertain', 'ERP_ERROR'],
            'invalid json' => ['failure', 200, 502, 'uncertain', 'ERP_ERROR'],
            'connection' => [null, 0, 503, 'uncertain', 'ERP_CONNECTION_ERROR'],
        ];
    }

    #[DataProvider('failures')]
    public function test_worker_failure_is_recorded_with_the_standard_contract(mixed $body, int $providerStatus, int $httpStatus, string $status, string $code): void
    {
        $result = $this->enqueue();
        Http::fake(function () use ($body, $providerStatus) {
            if ($providerStatus === 0) {
                throw new ConnectionException('Timeout.');
            }

            return Http::response($body, $providerStatus);
        });
        $outcome = resolve(RestErpJobService::class)->execute($result['data']['rest_erp_job_id']);
        $this->assertFalse($outcome['success']);
        $this->assertSame($httpStatus, $outcome['http_status']);
        $this->assertSame($code, $outcome['error']['code']);
        $job = RestErpJob::query()->sole();
        $this->assertSame($status, $job->status);
        $this->assertSame($code, $job->error_code);
        $this->assertSame($httpStatus, $job->http_status);
        $this->assertSame($providerStatus ?: null, $job->provider_http_status);
        $this->assertSame(1, $job->tries);
        $this->assertSame($providerStatus === 0 ? 'rest_erp_created' : 'rest_erp_'.$status, $job->actionLogs()->orderByDesc('id')->first()->action);
    }

    private const LOCATION_ERROR = 'Please Check Location.  CorrelationId:  cc5fc117-ebd0-4e7b-a67c-109b95ab3619.';

    public static function erpErrors(): array
    {
        return [
            'provided location error' => [['error' => ['code' => 'Application_DialogException', 'message' => self::LOCATION_ERROR]], 400, 'Application_DialogException', self::LOCATION_ERROR, 422, 'failed'],
            'different business code' => [['error' => ['code' => 'BadRequest_InvalidParameter', 'message' => 'Destination is invalid.']], 400, 'BadRequest_InvalidParameter', 'Destination is invalid.', 502, 'failed'],
            'server error message' => [['error' => ['code' => 'Internal_ServerError', 'message' => 'ERP could not create the request.']], 500, 'Internal_ServerError', 'ERP could not create the request.', 502, 'uncertain'],
            'error inside 200' => [['error' => ['code' => 'ERP_CustomError', 'message' => 'ERP returned an error.']], 200, 'ERP_CustomError', 'ERP returned an error.', 502, 'uncertain'],
            'auth error message' => [['error' => ['code' => 'Authentication_Invalid', 'message' => 'ERP authentication was denied.']], 401, 'Authentication_Invalid', 'ERP authentication was denied.', 502, 'failed'],
            'message value object' => [['error' => ['code' => 'Application_DialogException', 'message' => ['lang' => 'en', 'value' => self::LOCATION_ERROR]]], 400, 'Application_DialogException', self::LOCATION_ERROR, 422, 'failed'],
            'message without code' => [['error' => ['message' => 'Destination does not exist.']], 400, 'ERP_ERROR', 'Destination does not exist.', 502, 'failed'],
            'string error' => [['error' => 'Destination does not exist.'], 400, 'ERP_ERROR', 'Destination does not exist.', 502, 'failed'],
        ];
    }

    #[DataProvider('erpErrors')]
    public function test_erp_message_is_returned_and_preserved_in_job_audit_and_application_log(array $body, int $providerStatus, string $code, string $message, int $httpStatus, string $status): void
    {
        $queued = $this->enqueue();
        Http::fake([self::URL => Http::response($body, $providerStatus)]);
        Log::spy();
        $result = resolve(RestErpJobService::class)->execute($queued['data']['rest_erp_job_id']);
        $message = "\u{2067}خطای بیزینس سنترال :: \u{2068}{$message} \u{2069}\u{2069}";
        $this->assertSame(['code' => $code, 'message' => $message], $result['error']);
        $this->assertSame($httpStatus, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame($code, $job->error_code);
        $this->assertSame($message, $job->description);
        $this->assertSame($providerStatus, $job->provider_http_status);
        $this->assertSame($status, $job->status);
        $log = $job->actionLogs()->latest('id')->first();
        $this->assertSame($message, $log->data['snapshot']['description']);
        $this->assertArrayNotHasKey('result', $log->data);
        Log::shouldHaveReceived('warning')->with($message, \Mockery::on(fn ($context) => $context['rest_erp_job_id'] === $job->id && $context['error_code'] === $code))->once();
        Http::assertSentCount(1);
    }

    public static function httpExceptions(): array
    {
        return ['laravel response exception' => ['laravel'], 'guzzle response exception' => ['guzzle']];
    }

    #[DataProvider('httpExceptions')]
    public function test_http_exception_response_does_not_hide_the_original_erp_error(string $type): void
    {
        $queued = $this->enqueue();
        $response = new PsrResponse(400, ['Content-Type' => 'application/json'], json_encode(['error' => [
            'code' => 'Application_DialogException', 'message' => self::LOCATION_ERROR,
        ]]));
        Http::fake(function () use ($type, $response) {
            if ($type === 'laravel') {
                throw new RequestException(new ClientResponse($response));
            }
            throw new GuzzleRequestException('password=exception-secret', new PsrRequest('POST', self::URL), $response);
        });
        $result = resolve(RestErpJobService::class)->execute($queued['data']['rest_erp_job_id']);
        $this->assertSame(['code' => 'Application_DialogException', 'message' => "\u{2067}خطای بیزینس سنترال :: \u{2068}".self::LOCATION_ERROR." \u{2069}\u{2069}"], $result['error']);
        $this->assertSame(422, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('failed', $job->status);
        $this->assertSame(400, $job->provider_http_status);
        $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2068}".self::LOCATION_ERROR." \u{2069}\u{2069}", $job->description);
        $this->assertStringNotContainsString('exception-secret', json_encode([$job->toArray(), $job->actionLogs()->get()->toArray()]));
    }

    public function test_sync_result_and_job_api_show_the_provided_location_error(): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'erp-jobs', 'guard_name' => 'web']));
        Http::fake([self::URL => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => self::LOCATION_ERROR]], 400)]);
        $result = resolve(ExitRequestsService::class)->send(self::PAYLOAD['VIN'], 1, 'CT004156', 2)->sync();
        $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2068}".self::LOCATION_ERROR." \u{2069}\u{2069}", $result['error']['message']);
        $job = RestErpJob::query()->sole();
        $this->getJson('/api/rest-erp-jobs/'.$job->id)->assertOk()->assertJsonPath('data.description', "\u{2067}خطای بیزینس سنترال :: \u{2068}".self::LOCATION_ERROR." \u{2069}\u{2069}");
        $this->getJson('/api/rest-erp-jobs')->assertOk()->assertJsonPath('data.0.description', "\u{2067}خطای بیزینس سنترال :: \u{2068}".self::LOCATION_ERROR." \u{2069}\u{2069}");
        $actions = $this->getJson('/api/rest-erp-jobs/'.$job->id.'/actions')->assertOk();
        $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2068}".self::LOCATION_ERROR." \u{2069}\u{2069}", $actions->json('data.1.data.snapshot.description'));
        Queue::assertNothingPushed();
    }

    public function test_erp_credentials_are_redacted_consistently_in_the_result_and_logs(): void
    {
        Http::fake([self::URL => Http::response(['error' => [
            'code' => 'Application_DialogException', 'message' => 'Please Check Location. test-password test-user password=log-secret',
        ]], 400)]);
        $result = resolve(ExitRequestsService::class)->send(self::PAYLOAD['VIN'], 1, 'CT004156', 2)->sync();
        $job = RestErpJob::query()->sole();
        $this->assertSame($result['error']['message'], $job->description);
        $this->assertStringEndsWith(" \u{2069}\u{2069}", $job->description);
        $serialized = json_encode([$result, $job->toArray(), $job->actionLogs()->get()->toArray()]);
        foreach (['log-secret', 'test-password', 'test-user'] as $secret) {
            $this->assertStringNotContainsString($secret, $serialized);
        }
        $this->assertStringContainsString('Please Check Location.', $job->description);
    }

    public function test_exception_without_a_response_is_not_presented_as_an_erp_business_message(): void
    {
        $queued = $this->enqueue();
        Http::fake(fn () => throw new RuntimeException('password=internal-secret'));
        $result = resolve(RestErpJobService::class)->execute($queued['data']['rest_erp_job_id']);
        $this->assertSame('ERP_EXECUTION_UNCERTAIN', $result['error']['code']);
        $this->assertSame('RuntimeException: password=[REDACTED]', $result['error']['message']);
        $job = RestErpJob::query()->sole();
        $this->assertNull($job->provider_http_status);
        $this->assertSame('uncertain', $job->status);
        $this->assertSame($result['error']['message'], $job->description);
        $this->assertStringNotContainsString('internal-secret', json_encode([$job->toArray(), $job->actionLogs()->get()->toArray()]));
    }
}
