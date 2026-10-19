<?php

namespace Tests\Feature;

use App\Models\RestErpJob;
use App\Services\Erp\RestApi\ApproveExitZonesService;
use App\Services\Erp\RestApi\ApproveReturnZonesService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class ApproveZonesServiceTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Bus::fake();
        Queue::fake();
    }

    public static function operations(): array
    {
        return [
            'exit' => [ApproveExitZonesService::class, 'ApproveExitZones', 'BarcodeInfo', 'N5RSU7SN5R2902014', 'تأیید خروج خودرو'],
            'return' => [ApproveReturnZonesService::class, 'ApproveReturnZones', 'VIN', 'N5RSU7SN5P2901594', 'تأیید ورود خودرو'],
        ];
    }

    #[DataProvider('operations')]
    public function test_exact_json_payload_and_sync_audit(string $service, string $endpoint, string $field, string $identifier, string $section): void
    {
        $body = ['accepted' => true];
        Http::fake(function (Request $request, array $options) use ($body) {
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(15, $options['timeout']);
            $this->assertFalse($options['allow_redirects']);

            return Http::response($body, 201);
        });

        $result = resolve($service)->send($identifier, 2);

        $this->assertSame(['success' => true, 'data' => $body, 'error' => null, 'http_status' => 201], $result);
        $payload = [$field => $identifier, 'ItemType' => '2'];
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === config('rest_erp.base_url').'/'.$endpoint
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('Accept', 'application/json')
            && $request->data() === $payload);
        $job = RestErpJob::query()->sole();
        $this->assertSame($service, $job->service);
        $this->assertSame($endpoint, $job->endpoint);
        $this->assertSame($section, $job->section);
        $this->assertSame($payload, $job->parameters);
        $this->assertSame($payload, $job->toArray()['parameters']);
        $this->assertSame($payload, $job->request_payload);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame('success', $job->status);
        $this->assertSame(1, $job->tries);
        $this->assertSame(201, $job->http_status);
        $this->assertSame(201, $job->provider_http_status);
        $this->assertNull($job->queue);
        $this->assertNull($job->connection);
        $this->assertNull($job->queued_at);
        $this->assertSame(['rest_erp_created', 'rest_erp_started', 'rest_erp_success'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        foreach ($job->actionLogs()->get() as $log) {
            $this->assertSame($payload, $log->data['snapshot']['parameters']);
            foreach (['client_computer_name', 'request_headers', 'client_ip', 'peer_ip', 'user_agent', 'response', 'response_headers', 'response_context'] as $removed) {
                $this->assertArrayNotHasKey($removed, $log->data['snapshot']);
                $this->assertArrayNotHasKey($removed, $job->toArray());
            }
        }
        $serialized = json_encode([$job->toArray(), $job->actionLogs()->get()->toArray()]);
        $this->assertStringNotContainsString('test-password', $serialized);
        $this->assertStringNotContainsString('test-user', $serialized);
        $this->assertDatabaseCount('erp_jobs', 0);
        Bus::assertNothingDispatched();
        Queue::assertNothingPushed();
        $this->assertFalse(method_exists($service, 'queue'));
    }

    #[DataProvider('operations')]
    public function test_validation_does_not_invent_identifier_formats_or_item_type_enums(string $service, string $endpoint, string $field): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $result = resolve($service)->send('custom-identifier', 'custom-item-type');
        $this->assertTrue($result['success']);
        Http::assertSent(fn (Request $request) => $request->data() === [$field => 'custom-identifier', 'ItemType' => 'custom-item-type']);
    }

    public static function invalidInputs(): array
    {
        $cases = [];
        foreach (self::operations() as $name => [$service]) {
            $cases[$name.' empty identifier'] = [$service, '', '2'];
            $cases[$name.' empty item type'] = [$service, 'N5RSU7SN5R2902014', ''];
        }

        return $cases;
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_payload_is_rejected_and_audited_without_sending(string $service, string $identifier, string $itemType): void
    {
        Http::fake();
        $result = resolve($service)->send($identifier, $itemType);
        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame(422, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('rejected', $job->status);
        $this->assertSame(['rest_erp_created', 'rest_erp_rejected'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        Http::assertNothingSent();
        Bus::assertNothingDispatched();
        Queue::assertNothingPushed();
    }

    public static function failures(): array
    {
        $cases = [];
        foreach (self::operations() as $name => [$service]) {
            foreach ([
                'business' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Approval refused.']], 400, 'Application_DialogException', 422, 'failed'],
                'auth' => [[], 401, 'ERP_AUTH_ERROR', 502, 'failed'],
                'server' => [[], 500, 'ERP_ERROR', 502, 'uncertain'],
                'invalid json' => ['<html>failure</html>', 200, 'ERP_ERROR', 502, 'uncertain'],
                'connection' => [null, 0, 'ERP_CONNECTION_ERROR', 503, 'uncertain'],
            ] as $failure => $arguments) {
                $cases[$name.' '.$failure] = [$service, ...$arguments];
            }
        }

        return $cases;
    }

    #[DataProvider('failures')]
    public function test_erp_failures_return_the_contract_and_keep_sync_audit(string $service, mixed $body, int $providerStatus, string $code, int $httpStatus, string $status): void
    {
        Http::fake(function () use ($body, $providerStatus) {
            if ($providerStatus === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $providerStatus);
        });
        $result = resolve($service)->send('N5RSU7SN5R2902014', 2);
        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertSame($code, $result['error']['code']);
        $this->assertSame($httpStatus, $result['http_status']);
        if ($code === 'Application_DialogException') {
            $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2066}Approval refused. \u{2069}\u{2069}", $result['error']['message']);
        }
        $job = RestErpJob::query()->sole();
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame($status, $job->status);
        $this->assertSame($code, $job->error_code);
        $this->assertSame($httpStatus, $job->http_status);
        $this->assertSame($providerStatus ?: null, $job->provider_http_status);
        $this->assertSame(['rest_erp_created', 'rest_erp_started', 'rest_erp_'.$status], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        Http::assertSentCount($providerStatus === 0 ? 0 : 1);
        Bus::assertNothingDispatched();
        Queue::assertNothingPushed();
    }
}
