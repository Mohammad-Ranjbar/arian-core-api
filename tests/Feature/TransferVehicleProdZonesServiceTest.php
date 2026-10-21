<?php

namespace Tests\Feature;

use App\Http\Controllers\SaleLocationController;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\TransferVehicleProdZonesService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class TransferVehicleProdZonesServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/TransferVehicleProdZones';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('rest_erp.base_url', dirname(self::ERP_URL, 3));
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    private function transfer(string $vin = 'N5RL40000S0001002', string $destination = 'PDI'): array
    {
        return (new ReflectionMethod(SaleLocationController::class, 'transfer'))
            ->invoke(resolve(SaleLocationController::class), $vin, $destination);
    }

    public function test_controller_posts_the_sample_payload_and_preserves_the_success_contract(): void
    {
        Http::fake([self::ERP_URL => Http::response(['VIN' => 'N5RL40000S0001002'], 201)]);

        $this->assertSame(['status' => 'ok', 'message' => ''], $this->transfer());

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === ['VIN' => 'N5RL40000S0001002', 'ToLocationType' => 'PDI']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('TransferVehicleProdZones', $job->endpoint);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame('success', $job->status);
    }

    public function test_destination_comes_from_the_sale_location_code(): void
    {
        Http::fake([self::ERP_URL => Http::response(['transferred' => true], 200)]);

        $this->assertSame('ok', $this->transfer(destination: 'Sales')['status']);

        Http::assertSent(fn (Request $request) => $request['ToLocationType'] === 'Sales');
    }

    public static function invalidInputs(): array
    {
        return [
            'empty vin' => ['', 'PDI'],
            'empty destination' => ['N5RL40000S0001002', ''],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_is_rejected_without_sending(string $vin, string $destination): void
    {
        Http::fake();

        $result = resolve(TransferVehicleProdZonesService::class)->send($vin, $destination)->sync();

        $this->assertFalse($result['success']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame('rejected', RestErpJob::query()->sole()->status);
        Http::assertNothingSent();
    }

    public static function failures(): array
    {
        return [
            'business error' => [[
                'error' => ['code' => 'Application_DialogException', 'message' => 'Transfer rejected.'],
            ], 400, 'Transfer rejected.', 'failed'],
            'authentication error' => [[], 401, 'کد وضعیت HTTP 401', 'failed'],
            'server error' => [[], 500, 'کد وضعیت HTTP 500', 'uncertain'],
            'connection error' => [null, 0, 'Connection timed out.', 'uncertain'],
        ];
    }

    #[DataProvider('failures')]
    public function test_controller_returns_erp_errors_and_audits_failures(mixed $body, int $status, string $message, string $jobStatus): void
    {
        Http::fake(function () use ($body, $status) {
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $status);
        });

        $result = $this->transfer();

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString($message, $result['message']);
        $this->assertSame($jobStatus, RestErpJob::query()->sole()->status);
    }
}
