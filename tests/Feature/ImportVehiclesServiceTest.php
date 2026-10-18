<?php

namespace Tests\Feature;

use App\Http\Controllers\PDIController;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\ImportVehiclesService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class ImportVehiclesServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/ImportVehicles';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('rest_erp.base_url', dirname(self::ERP_URL));
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
    }

    public function test_controller_posts_the_sample_payload_and_preserves_the_legacy_success_contract(): void
    {
        Http::fake([self::ERP_URL => Http::response(['VIN' => 'N5RF42CG5RP000020'], 201)]);

        $result = PDIController::sendPDIToErp('N5RF42CG5RP000020', [
            'pdi_result' => 'G1',
            'pdi_result_description' => 'Test',
            'pdi_result_description_2' => '',
            'engine_no' => '',
            'model_year' => '1405',
            'police_inspection_date' => '',
        ]);

        $this->assertSame(['status' => 'ok', 'message' => ''], $result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::ERP_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === [
                'VIN' => 'N5RF42CG5RP000020',
                'PDIResult' => 'G1',
                'PDIDescription' => 'Test',
                'PDIResultDesc' => '',
                'EngineNo' => '',
                'ModelYear' => '1405',
                'PoliceInspectionDate' => '',
            ]);
        $job = RestErpJob::query()->sole();
        $this->assertSame('ImportVehicles', $job->endpoint);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame('success', $job->status);
    }

    public function test_import_can_use_its_actor_without_a_logged_in_user_and_send_numeric_values_as_strings(): void
    {
        Auth::forgetGuards();
        Http::fake([self::ERP_URL => Http::response(['VIN' => 'N5RF42CG5RP000020'], 201)]);

        $result = PDIController::sendPDIToErp('N5RF42CG5RP000020', [
            'user_id' => 43,
            'engine_no' => 123456,
            'model_year' => 1405,
            'pdi_result' => null,
            'police_inspection_date' => null,
        ]);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(43, RestErpJob::query()->sole()->user_id);
        Http::assertSent(fn (Request $request) => $request->data() === [
            'VIN' => 'N5RF42CG5RP000020',
            'PDIResult' => '',
            'PDIDescription' => '',
            'PDIResultDesc' => '',
            'EngineNo' => '123456',
            'ModelYear' => '1405',
            'PoliceInspectionDate' => '',
        ]);
    }

    public function test_empty_vin_is_rejected_without_sending(): void
    {
        Http::fake();

        $result = resolve(ImportVehiclesService::class)->send('', []);

        $this->assertFalse($result['success']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertSame('rejected', RestErpJob::query()->sole()->status);
        Http::assertNothingSent();
    }

    public static function failures(): array
    {
        return [
            'business error' => [[
                'error' => ['code' => 'Application_DialogException', 'message' => 'PDI rejected.'],
            ], 400, 'PDI rejected.', 'failed'],
            'authentication error' => [[], 401, 'ERP HTTP 401', 'failed'],
            'connection error' => [null, 0, 'Connection timed out.', 'uncertain'],
        ];
    }

    #[DataProvider('failures')]
    public function test_controller_preserves_the_error_contract_and_audits_failures(mixed $body, int $status, string $message, string $jobStatus): void
    {
        Http::fake(function () use ($body, $status) {
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $status);
        });

        $result = PDIController::sendPDIToErp('N5RF42CG5RP000020', []);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString($message, $result['message']);
        $this->assertSame($jobStatus, RestErpJob::query()->sole()->status);
    }
}
