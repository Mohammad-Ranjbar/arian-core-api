<?php

namespace Tests\Feature;

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\VehicleController;
use App\Http\Resources\BarcodeResource;
use App\Models\Barcode;
use App\Models\RestErpJob;
use App\Models\Vehicle;
use App\Repositories\BarcodeRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class BarcodeStoreRestErpTest extends TestCase
{
    use CreatesRestErpTables;

    private const VIN = 'N5RPU3NN5T2604936';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->unsignedBigInteger('car_model_id');
            $table->softDeletes();
        });
        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
        });
        Schema::create('car_model_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_model_id');
        });
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->string('vin');
            $table->string('code');
            $table->string('type')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('car_models')->insert(['id' => 1, 'reference' => 'KSPCO010CP']);
        DB::table('vehicles')->insert(['vin' => self::VIN, 'car_model_id' => 1]);
    }

    private function controller(bool $erpSucceeds, bool $failLocalSave = false): BarcodeController
    {
        $vehicleController = Mockery::mock(VehicleController::class);
        if ($erpSucceeds && ! $failLocalSave) {
            $vehicleController->shouldReceive('getData')->once()->with(self::VIN)->andReturn(new Vehicle);
        } else {
            $vehicleController->shouldNotReceive('getData');
        }

        $controller = new class(resolve(BarcodeRepository::class), $vehicleController) extends BarcodeController
        {
            public bool $failLocalSave = false;

            public function afterSave($record, $data): mixed
            {
                if ($this->failLocalSave) {
                    throw new RuntimeException('Local save failed.');
                }

                return $record;
            }
        };
        $controller->failLocalSave = $failLocalSave;

        return $controller;
    }

    private function request(): Request
    {
        return Request::create('/barcodes', 'POST', [
            'vin' => self::VIN, 'code' => '0001234567890', 'type' => 'gearbox',
        ]);
    }

    public static function erpFailures(): array
    {
        return [
            'business error' => [400, 'failed'],
            'server error' => [500, 'uncertain'],
            'connection error' => [0, 'uncertain'],
        ];
    }

    #[DataProvider('erpFailures')]
    public function test_erp_failure_keeps_job_and_audit_without_saving_barcode(int $status, string $jobStatus): void
    {
        Http::fake(function () use ($status) {
            $this->assertSame(0, DB::transactionLevel());
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($status === 400
                ? ['error' => ['code' => 'Application_DialogException', 'message' => 'Tracking rejected.']]
                : [], $status);
        });

        $response = $this->controller(false)->store($this->request());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, DB::transactionLevel());
        $this->assertDatabaseCount('barcodes', 0);
        $job = RestErpJob::query()->sole();
        $this->assertSame($jobStatus, $job->status);
        $this->assertSame(['VIN' => self::VIN, 'BarCode' => '0001234567*890', 'ItemType' => '3'], $job->parameters);
        $this->assertSame(['rest_erp_created', ...($status === 0 ? [] : ['rest_erp_'.$jobStatus])], $job->actionLogs()->orderBy('id')->pluck('action')->all());
    }

    public function test_local_rollback_keeps_successful_erp_job(): void
    {
        Http::fake(['*/Trackings' => Http::response(['tracked' => true], 201)]);

        $response = $this->controller(true, true)->store($this->request());

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Local save failed.', $response->getData(true)['message']);
        $this->assertDatabaseCount('barcodes', 0);
        $this->assertSame('success', RestErpJob::query()->sole()->status);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_success_saves_normalized_barcode_and_keeps_erp_job(): void
    {
        Http::fake(['*/Trackings' => Http::response(['tracked' => true], 201)]);

        $response = $this->controller(true)->store($this->request());

        $this->assertInstanceOf(BarcodeResource::class, $response);
        $this->assertSame('0001234567*890', Barcode::query()->sole()->code);
        $this->assertSame('success', RestErpJob::query()->sole()->status);
        $this->assertSame(0, DB::transactionLevel());
    }
}
