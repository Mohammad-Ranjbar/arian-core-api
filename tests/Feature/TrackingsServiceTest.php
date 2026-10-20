<?php

namespace Tests\Feature;

use App\Http\Controllers\BarcodeController;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\TrackingsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class TrackingsServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/Trackings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('rest_erp.base_url', dirname(self::ERP_URL));
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    public function test_controller_posts_the_exact_sample_payload_and_preserves_the_success_contract(): void
    {
        Http::fake([self::ERP_URL => Http::response(['VIN' => 'N5RPU3NN5T2604936'], 201)]);

        $result = resolve(BarcodeController::class)->submitSerialBarcode('N5RPU3NN5T2604936', '88866', '3');

        $this->assertSame(['status' => 'ok', 'message' => ''], $result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === [
                'VIN' => 'N5RPU3NN5T2604936',
                'BarCode' => '88866',
                'ItemType' => '3',
            ]);
        $job = RestErpJob::query()->sole();
        $this->assertSame('Trackings', $job->endpoint);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame('success', $job->status);
    }

    public function test_numeric_item_type_is_sent_as_string_and_barcode_keeps_leading_zeros_and_separator(): void
    {
        Http::fake([self::ERP_URL => Http::response(['tracked' => true], 200)]);

        $result = resolve(BarcodeController::class)->submitSerialBarcode('N5RPU3NN5T2604936', '0001234567*890', 2);

        $this->assertSame('ok', $result['status']);
        Http::assertSent(fn (Request $request) => $request['BarCode'] === '0001234567*890'
            && $request['ItemType'] === '2');
    }

    public static function invalidInputs(): array
    {
        return [
            'empty vin' => ['', '88866', '3'],
            'empty barcode' => ['N5RPU3NN5T2604936', '', '3'],
            'empty item type' => ['N5RPU3NN5T2604936', '88866', ''],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_is_rejected_without_sending(string $vin, string $barcode, string $itemType): void
    {
        Http::fake();

        $result = resolve(TrackingsService::class)->send($vin, $barcode, $itemType)->sync();

        $this->assertFalse($result['success']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame('rejected', RestErpJob::query()->sole()->status);
        Http::assertNothingSent();
    }

    public static function failures(): array
    {
        return [
            'business error' => [[
                'error' => ['code' => 'Application_DialogException', 'message' => 'Tracking rejected.'],
            ], 400, 'Tracking rejected.', 'failed'],
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

        $result = resolve(BarcodeController::class)->submitSerialBarcode('N5RPU3NN5T2604936', '88866', 3);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString($message, $result['message']);
        $this->assertSame($jobStatus, RestErpJob::query()->sole()->status);
    }
}
