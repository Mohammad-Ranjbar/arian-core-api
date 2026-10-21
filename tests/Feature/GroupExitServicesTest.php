<?php

namespace Tests\Feature;

use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\ExitPrintsService;
use App\Services\Erp\RestApi\GroupExitsService;
use App\Services\Erp\RestApi\GroupReturnsService;
use App\Services\Erp\RestApi\RestErpJobService;
use App\Services\Erp\RestApi\ReturnRequestsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class GroupExitServicesTest extends TestCase
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

    public static function operations(): array
    {
        return [
            'group' => [GroupExitsService::class, 'GroupExits', ['N5RT92CF6SP000445,N5RT92CF6SP000444', 1, 'CT041381', 2, 2], [
                'VINArray' => 'N5RT92CF6SP000445,N5RT92CF6SP000444', 'TypeOfExit' => '1', 'Destination' => 'CT041381', 'NumberVIN' => '2', 'ItemType' => '2',
            ]],
            'return' => [GroupReturnsService::class, 'GroupReturns', ['N5RTLS4M5S2101126,N5RTLS4M5S2101125', 'CT004156', 2, 2], [
                'VINArray' => 'N5RTLS4M5S2101126,N5RTLS4M5S2101125', 'Destination' => 'CT004156', 'NumberVIN' => '2', 'ItemTypeP' => '2',
            ]],
            'single return' => [ReturnRequestsService::class, 'ReturnRequests', ['JE4LSW140TZ700439', 'CT006030', 2], [
                'VIN' => 'JE4LSW140TZ700439', 'Destination' => 'CT006030', 'ItemType' => '2',
            ]],
            'print' => [ExitPrintsService::class, 'ExitPrints', ['JE4LSW140TZ700411', 2], ['VIN' => 'JE4LSW140TZ700411', 'ItemType' => '2']],
        ];
    }

    #[DataProvider('operations')]
    public function test_exact_queue_payload_and_worker_http_contract(string $service, string $endpoint, array $arguments, array $payload): void
    {
        $result = resolve($service)->queue(...$arguments);
        $this->assertTrue($result['success']);
        $this->assertSame(202, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame($payload, $job->parameters);
        $this->assertSame($service, $job->service);
        $this->assertSame('queue', $job->execution_mode);
        $this->assertSame('pending', $job->status);
        Queue::assertPushedOn('parallel', SendToRestErpJob::class, fn ($queued) => $queued->connection === 'redis');
        Http::assertNothingSent();
        Http::fake(function (Request $request, array $options) {
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(300, $options['timeout']);

            return Http::response(['accepted' => true], 201);
        });
        $outcome = resolve(RestErpJobService::class)->execute($job->id);
        $this->assertSame(['success' => true, 'data' => ['accepted' => true], 'error' => null, 'http_status' => 201], $outcome);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === config('rest_erp.base_url').'/product/v1.0/'.$endpoint
            && $request->method() === 'POST' && $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('Accept', 'application/json') && $request->data() === $payload);
        $job->refresh();
        $this->assertSame('success', $job->status);
        $this->assertSame(201, $job->provider_http_status);
        $this->assertSame(1, $job->tries);
        foreach ($job->actionLogs()->get() as $log) {
            $this->assertSame($payload, $log->data['snapshot']['parameters']);
        }
        $serialized = json_encode([$job->toArray(), $job->actionLogs()->get()->toArray()]);
        $this->assertStringNotContainsString('test-password', $serialized);
        $this->assertStringNotContainsString('test-user', $serialized);
        foreach (['client_computer_name', 'request_headers', 'client_ip', 'peer_ip', 'user_agent', 'response', 'response_headers', 'response_context'] as $removed) {
            $this->assertArrayNotHasKey($removed, $job->toArray());
        }
        $this->assertDatabaseCount('erp_jobs', 0);
    }

    #[DataProvider('operations')]
    public function test_send_is_audited_and_does_not_queue(string $service, string $endpoint, array $arguments): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $result = resolve($service)->send(...$arguments)->sync();
        $this->assertSame(['success' => true, 'data' => [], 'error' => null, 'http_status' => 200], $result);
        $this->assertSame('sync', RestErpJob::query()->sole()->execution_mode);
        Queue::assertNothingPushed();
    }

    public static function invalidInputs(): array
    {
        $cases = [];
        foreach (self::operations() as $name => [$service, $endpoint, $arguments]) {
            foreach (array_keys($arguments) as $index) {
                $invalid = $arguments;
                $invalid[$index] = '';
                $cases[$name.' empty '.$index] = [$service, $invalid];
            }
        }
        $cases['zero count'] = [GroupExitsService::class, ['custom-1,custom-2', 'custom-type', 'custom-destination', 0, 'custom-item']];

        $cases['return zero count'] = [GroupReturnsService::class, ['custom-1,custom-2', 'custom-destination', 0, 'custom-item']];

        return $cases;
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_payload_is_rejected_before_dispatch(string $service, array $arguments): void
    {
        $result = resolve($service)->queue(...$arguments);
        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['http_status']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame('rejected', RestErpJob::query()->sole()->status);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public static function failures(): array
    {
        $cases = [];
        foreach (self::operations() as $name => [$service, $endpoint, $arguments]) {
            foreach ([
                'business' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Request refused.']], 400, 422, 'failed', 'Application_DialogException'],
                'auth' => [[], 401, 502, 'failed', 'ERP_AUTH_ERROR'],
                'server' => [[], 500, 502, 'uncertain', 'ERP_ERROR'],
                'connection' => [null, 0, 503, 'uncertain', 'ERP_CONNECTION_ERROR'],
            ] as $failure => $values) {
                $cases[$name.' '.$failure] = [$service, $arguments, ...$values];
            }
        }

        return $cases;
    }

    #[DataProvider('failures')]
    public function test_worker_failures_follow_the_shared_contract(string $service, array $arguments, mixed $body, int $providerStatus, int $httpStatus, string $status, string $code): void
    {
        $queued = resolve($service)->queue(...$arguments);
        Http::fake(function () use ($body, $providerStatus) {
            if ($providerStatus === 0) {
                throw new ConnectionException('Timeout.');
            }

            return Http::response($body, $providerStatus);
        });
        $result = resolve(RestErpJobService::class)->execute($queued['data']['rest_erp_job_id']);
        $this->assertFalse($result['success']);
        $this->assertSame($httpStatus, $result['http_status']);
        $this->assertSame($code, $result['error']['code']);
        $job = RestErpJob::query()->sole();
        $this->assertSame($status, $job->status);
        $this->assertSame($code, $job->error_code);
        $this->assertSame('rest_erp_'.$status, $job->actionLogs()->orderByDesc('id')->first()->action);
    }

    public function test_validation_does_not_invent_vin_formats_or_enums(): void
    {
        $result = resolve(GroupExitsService::class)->queue('custom-1,custom-2', 'custom-type', 'custom-destination', 2, 'custom-item');
        $this->assertTrue($result['success']);
        $result = resolve(ExitPrintsService::class)->queue('custom-1', 'custom-item');
        $this->assertTrue($result['success']);
    }
}
