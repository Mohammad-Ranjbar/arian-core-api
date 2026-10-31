<?php

namespace Tests\Feature;

use App\Models\RestErpJob;
use App\Services\Erp\RestApi\VINChangesService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class VINChangesServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/VINChanges';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('rest_erp.base_url', 'http://172.16.0.154:7048/PreBC/api/arianCapital');
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
    }

    public function test_it_posts_the_exact_sample_payload_and_preserves_the_explicit_actor(): void
    {
        Http::fake(function (Request $request, array $options) {
            $this->assertSame(['test-user', 'test-password', 'ntlm'], $options['auth']);

            return Http::response(['VIN' => 'N5RL40000S0003333'], 201);
        });

        $result = resolve(VINChangesService::class)->send(...array_merge($this->arguments(), ['userId' => 43]))->sync();

        $this->assertTrue($result['success']);
        $this->assertSame(201, $result['http_status']);
        $this->assertSame(['VIN' => 'N5RL40000S0003333'], $result['data']);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === [
                'ProductionNo' => '05PRO0099915',
                'TempVIN' => 'N5RL40000S0002064',
                'VIN' => 'N5RL40000S0003333',
            ]);
        $job = RestErpJob::query()->sole();
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame(43, $job->user_id);
        $this->assertSame('تغییر شماره شاسی خودرو', $job->section);
        $this->assertSame(['rest_erp_success'], $job->actionLogs()->pluck('action')->all());
        Queue::assertNothingPushed();
    }

    public function test_dispatch_defaults_to_sync(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $result = resolve(VINChangesService::class)->dispatch(...$this->arguments());

        $this->assertSame(200, $result['http_status']);
        $this->assertSame('sync', RestErpJob::query()->sole()->execution_mode);
        Queue::assertNothingPushed();
        Http::assertSentCount(1);
    }

    public static function emptyFields(): array
    {
        return [
            'production number' => [['productionNo' => ''], 'شماره سفارش تولید'],
            'new VIN' => [['vin' => ''], 'شماره شاسی'],
        ];
    }

    #[DataProvider('emptyFields')]
    public function test_empty_fields_are_rejected_with_persian_validation(array $overrides, string $label): void
    {
        $result = resolve(VINChangesService::class)->send(...array_replace($this->arguments(), $overrides))->sync();

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['http_status']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertStringContainsString($label, $result['error']['message']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('rejected', $job->status);
        $this->assertSame(0, $job->actionLogs()->count());
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_business_errors_preserve_the_provider_message(): void
    {
        Http::fake(['*' => Http::response(['error' => [
            'code' => 'Application_DialogException', 'message' => 'VIN change rejected.',
        ]], 400)]);

        $result = resolve(VINChangesService::class)->send(...$this->arguments())->sync();

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['http_status']);
        $this->assertSame('Application_DialogException', $result['error']['code']);
        $this->assertStringContainsString('VIN change rejected.', $result['error']['message']);
        $this->assertSame(['rest_erp_failed'], RestErpJob::query()->sole()->actionLogs()->pluck('action')->all());
    }

    public function test_an_empty_optional_temporary_vin_is_sent_unchanged(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $result = resolve(VINChangesService::class)->send(...array_replace($this->arguments(), ['tempVin' => '']))->sync();

        $this->assertTrue($result['success']);
        Http::assertSent(fn (Request $request) => $request['TempVIN'] === '');
    }

    public function test_connection_failures_are_uncertain_without_an_action_log(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out.'));

        $result = resolve(VINChangesService::class)->send(...$this->arguments())->sync();

        $this->assertFalse($result['success']);
        $this->assertSame(503, $result['http_status']);
        $this->assertSame('ERP_CONNECTION_ERROR', $result['error']['code']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('uncertain', $job->status);
        $this->assertSame(0, $job->actionLogs()->count());
        Queue::assertNothingPushed();
    }

    private function arguments(): array
    {
        return ['productionNo' => '05PRO0099915', 'tempVin' => 'N5RL40000S0002064', 'vin' => 'N5RL40000S0003333'];
    }
}
