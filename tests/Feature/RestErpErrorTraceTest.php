<?php

namespace Tests\Feature;

use App\Exceptions\RestErpQueueException;
use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\RestErpJobService;
use App\Services\Erp\RestApi\RestErpLogSanitizer;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class RestErpErrorTraceTest extends TestCase
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

    public static function endpoints(): array
    {
        return [
            'qc' => ['qcStatuses', ['VIN' => 'N5RT92CF6SP000481', 'QCStatus' => '84', 'ItemType' => '2', 'QCType' => '1']],
            'transfer' => ['CreateTransferZones', [
                'ItemNoArray' => 'ITEM-1', 'ItemQtyArray' => '1', 'NumberItems' => '1',
                'FromLocationType' => 'Central', 'ToLacationType' => 'Production', 'VariantArray' => '',
                'CrossRefArray' => '', 'MakeCode' => 'LAMARI', 'DocumentNo' => '77',
            ]],
            'basket' => ['ItemReclassJournals', ['ArrayCrossReferenceNo' => 'BK-1', 'BatchNo' => 'TEST', 'Number' => '1', 'BatchName' => 'LAMARI']],
            'approve exit' => ['ApproveExitZones', ['BarcodeInfo' => 'N5RT92CF6SP000481', 'ItemType' => '2']],
            'approve return' => ['ApproveReturnZones', ['VIN' => 'N5RT92CF6SP000481', 'ItemType' => '2']],
            'exit request' => ['ExitRequests', ['VIN' => 'N5RT92CF6SP000481', 'TypeOfExit' => '1', 'Destination' => 'CT004156', 'ItemType' => '2']],
            'group' => ['GroupExits', ['VINArray' => 'N5RT92CF6SP000481,N5RT92CF6SP000482', 'TypeOfExit' => '1', 'Destination' => 'CT004156', 'NumberVIN' => '2', 'ItemType' => '2']],
            'print' => ['ExitPrints', ['VIN' => 'N5RT92CF6SP000481', 'ItemType' => '2']],
        ];
    }

    private function assertAuditedMessage(string $message): RestErpJob
    {
        $job = RestErpJob::query()->sole();
        $this->assertSame($message, $job->description);
        $audit = $job->actionLogs()->latest('id')->first();
        $this->assertSame($message, $audit->data['snapshot']['description']);
        $this->assertSame($message, $audit->data['result']['description']);

        return $job;
    }

    #[DataProvider('endpoints')]
    public function test_each_endpoint_preserves_erp_error_code_message_and_correlation_id(string $endpoint, array $payload): void
    {
        $message = 'Destination rejected. CorrelationId: 2c4d0415-38ce-4cd8-bef5-384c351f8bf9.';
        Http::fake(['*' => Http::response(['error' => ['code' => 'BadRequest_InvalidParameter', 'message' => $message]], 400)]);
        Log::spy();
        $result = resolve(RestErpJobService::class)->send($endpoint, $payload);
        $message = "\u{2067}خطای بیزینس سنترال :: \u{2066}{$message} \u{2069}\u{2069}";
        $this->assertSame(['code' => 'BadRequest_InvalidParameter', 'message' => $message], $result['error']);
        $job = $this->assertAuditedMessage($message);
        $this->assertSame('BadRequest_InvalidParameter', $job->error_code);
        $this->assertSame(400, $job->provider_http_status);
        $this->assertSame('failed', $job->status);
        Log::shouldHaveReceived('warning')->with($message, \Mockery::on(fn ($context) => $context['request_id'] === $job->uuid))->once();
    }

    #[DataProvider('endpoints')]
    public function test_each_endpoint_preserves_response_body_from_http_exceptions(string $endpoint, array $payload): void
    {
        Http::fake(fn () => throw new RequestException(new Response(new PsrResponse(500, [], json_encode([
            'error' => ['code' => 'Internal_ServerError', 'message' => 'ERP processing failed.'],
        ])))));
        $result = resolve(RestErpJobService::class)->send($endpoint, $payload);
        $this->assertSame(['code' => 'Internal_ServerError', 'message' => "\u{2067}خطای بیزینس سنترال :: \u{2066}ERP processing failed. \u{2069}\u{2069}"], $result['error']);
        $job = $this->assertAuditedMessage("\u{2067}خطای بیزینس سنترال :: \u{2066}ERP processing failed. \u{2069}\u{2069}");
        $this->assertSame(500, $job->provider_http_status);
        $this->assertSame('uncertain', $job->status);
    }

    #[DataProvider('endpoints')]
    public function test_each_endpoint_returns_connection_exception_detail_and_previous_cause(string $endpoint, array $payload): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: request timed out. password=wire-secret', 0, new RuntimeException('Socket timeout.')));
        $result = resolve(RestErpJobService::class)->send($endpoint, $payload);
        $this->assertSame('ERP_CONNECTION_ERROR', $result['error']['code']);
        $this->assertStringContainsString('cURL error 28: request timed out.', $result['error']['message']);
        $this->assertStringContainsString('Socket timeout.', $result['error']['message']);
        $this->assertStringNotContainsString('wire-secret', $result['error']['message']);
        $job = $this->assertAuditedMessage($result['error']['message']);
        $this->assertSame('uncertain', $job->status);
        $this->assertNull($job->provider_http_status);
    }

    public function test_preflight_integrity_failure_preserves_its_actual_reason(): void
    {
        $queued = resolve(RestErpJobService::class)->enqueue('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2']);
        $job = RestErpJob::query()->findOrFail($queued['data']['rest_erp_job_id']);
        $job->update(['parameters' => ['VIN' => 'CHANGED', 'ItemType' => '2']]);
        $result = resolve(RestErpJobService::class)->execute($job->id);
        $this->assertSame('ERP_JOB_ERROR', $result['error']['code']);
        $this->assertSame('InvalidArgumentException: REST ERP payload integrity check failed.', $result['error']['message']);
        $this->assertAuditedMessage($result['error']['message']);
        Http::assertNothingSent();
    }

    public function test_broker_failure_response_and_logs_preserve_the_same_safe_exception_message(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Redis connection refused. password=broker-secret'));
        try {
            resolve(RestErpJobService::class)->enqueue('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2']);
            $this->fail('Expected broker failure.');
        } catch (RestErpQueueException $exception) {
            $message = $exception->getResponse()->getData(true)['error']['message'];
            $this->assertSame('RuntimeException: Redis connection refused. password=[REDACTED]', $message);
            $job = $this->assertAuditedMessage($message);
            $this->assertSame('ERP_QUEUE_ERROR', $job->error_code);
            $this->assertSame(503, $exception->getResponse()->getStatusCode());
        }
    }

    public function test_worker_failure_preserves_the_actual_exception_without_hiding_execution_uncertainty(): void
    {
        $queued = resolve(RestErpJobService::class)->enqueue('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2']);
        $job = RestErpJob::query()->findOrFail($queued['data']['rest_erp_job_id']);
        $job->update(['status' => 'in-progress', 'tries' => 1, 'started_at' => now()]);
        (new SendToRestErpJob((string) $job->id))->failed(new RuntimeException('Worker terminated while reading the ERP response.'));
        $this->assertAuditedMessage('RuntimeException: Worker terminated while reading the ERP response.');
        $this->assertSame('uncertain', $job->fresh()->status);
        $this->assertSame('ERP_EXECUTION_UNCERTAIN', $job->fresh()->error_code);
    }

    public function test_non_json_error_and_authentication_response_are_described_with_their_http_status(): void
    {
        Http::fakeSequence()->push('<html><body>Upstream gateway failed.</body></html>', 502)->push('Access denied.', 403);
        $result = resolve(RestErpJobService::class)->send('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2']);
        $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2066}ERP HTTP 502: Upstream gateway failed. \u{2069}\u{2069}", $result['error']['message']);
        $result = resolve(RestErpJobService::class)->send('ExitPrints', ['VIN' => 'VIN-2', 'ItemType' => '2']);
        $this->assertSame('ERP_AUTH_ERROR', $result['error']['code']);
        $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2066}ERP HTTP 403: Access denied. \u{2069}\u{2069}", $result['error']['message']);
    }

    public function test_missing_configuration_is_named_without_disclosing_credential_values(): void
    {
        config()->set('erp.auth', ['', '', 'ntlm']);
        $result = resolve(RestErpJobService::class)->send('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2']);
        $this->assertSame('ERP_CONFIGURATION_ERROR', $result['error']['code']);
        $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2066}Missing configuration: ERP username, ERP password \u{2069}\u{2069}", $result['error']['message']);
        $this->assertAuditedMessage($result['error']['message']);
        Http::assertNothingSent();
    }

    public function test_json_encoding_failure_preserves_its_reason(): void
    {
        $result = resolve(RestErpJobService::class)->enqueue('ExitPrints', ['VIN' => "\xFF", 'ItemType' => '2']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertStringContainsString('Malformed UTF-8', $result['error']['message']);
        $this->assertAuditedMessage($result['error']['message']);
        Queue::assertNothingPushed();
    }

    public function test_error_sanitizing_masks_configured_credentials_json_credentials_and_ntlm_tokens(): void
    {
        $sanitizer = resolve(RestErpLogSanitizer::class);
        $text = 'Failure. test-user test-password {"password":"secret with spaces","token":"json-secret"} NTLM TlRMTVNTUAABAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
        $safe = $sanitizer->errorText($text);
        foreach (['test-user', 'test-password', 'secret with spaces', 'json-secret', 'TlRMTVNTUAABAAAAAAAAAAAAAAAAAAAAAAAAAAAA'] as $secret) {
            $this->assertStringNotContainsString($secret, $safe);
        }
        $this->assertStringContainsString('Failure.', $safe);
    }
}
