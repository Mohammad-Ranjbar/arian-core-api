<?php

namespace Tests\Feature;

use App\Http\Controllers\ChangeVinController;
use App\Http\Controllers\VehicleController;
use App\Http\Requests\StoreChangeVinRequest;
use App\Http\Resources\ChangeVinResource;
use App\Models\ChangeVin;
use App\Models\ProductionLine;
use App\Models\RestErpJob;
use App\Models\Tracking;
use App\Models\Vehicle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class ChangeVinRestErpTest extends TestCase
{
    use CreatesRestErpTables;

    private const OLD_VIN = 'N5RL40000S0002064';
    private const VIN = 'N5RL40000S0003333';
    private const PRODUCTION_NO = '05PRO0099915';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        config()->set('rest_erp.endpoints.VINChanges.mode', 'unique');
        Http::preventStrayRequests();
        Queue::fake();
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->string('production_order_number');
            $table->unsignedBigInteger('car_model_id')->nullable();
            $table->unsignedBigInteger('make_code_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        foreach ([
            '2023_11_01_134413_create_production_trackings_table.php',
            '2026_09_13_100000_add_soft_deletes_to_production_tracking_table.php',
            '2025_08_02_160622_create_change_vins_table.php',
            '2025_07_30_085303_create_vin_engines_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('production_tracking', fn (Blueprint $table) => $table->boolean('is_systemic')->default(false));
        DB::table('vehicles')->insert(['vin' => self::OLD_VIN, 'production_order_number' => self::PRODUCTION_NO]);
        DB::table('production_tracking')->insert([
            'vin' => self::OLD_VIN, 'type' => 'enter', 'model_type' => ProductionLine::class, 'model_id' => 7, 'user_id' => 42,
        ]);
        DB::table('vin_engines')->insert(['vin' => self::VIN, 'engine_no' => 'ENGINE-1', 'production_order_number' => 'OLD-PO']);
    }

    private function controller(bool $refreshVehicle): ChangeVinController
    {
        $vehicles = Mockery::mock(VehicleController::class);
        if ($refreshVehicle) {
            $vehicles->shouldReceive('getData')->once()->with(self::VIN)->andReturnUsing(function () {
                $this->assertGreaterThan(0, DB::transactionLevel());
                $this->assertSame('success', RestErpJob::query()->sole()->status);

                return Vehicle::query()->create(['vin' => self::VIN, 'production_order_number' => self::PRODUCTION_NO]);
            });
        } else {
            $vehicles->shouldNotReceive('getData');
        }

        return new ChangeVinController($vehicles);
    }

    private function request(array $overrides = []): StoreChangeVinRequest
    {
        $request = Mockery::mock(StoreChangeVinRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(array_replace([
            'production_order_number' => self::PRODUCTION_NO, 'old_vin' => self::OLD_VIN, 'vin' => self::VIN,
        ], $overrides));

        return $request;
    }

    public function test_erp_runs_synchronously_before_local_changes_and_preserves_the_success_response(): void
    {
        Http::fake(function () {
            $this->assertSame(0, DB::transactionLevel());
            $this->assertDatabaseCount('change_vins', 0);
            $this->assertDatabaseMissing('vehicles', ['vin' => self::VIN]);

            return Http::response([], 200);
        });

        $response = $this->controller(true)->store($this->request());

        $this->assertInstanceOf(ChangeVinResource::class, $response);
        $this->assertSame(self::VIN, ChangeVin::query()->sole()->vin);
        $this->assertDatabaseHas('production_tracking', [
            'vin' => self::VIN, 'model_type' => ProductionLine::class, 'model_id' => 7,
            'type' => 'enter', 'user_id' => 42, 'is_systemic' => true,
        ]);
        $this->assertDatabaseHas('vin_engines', ['vin' => self::VIN, 'production_order_number' => self::PRODUCTION_NO]);
        $job = RestErpJob::query()->sole();
        $this->assertSame(Vehicle::class, $job->model_type);
        $this->assertSame(self::OLD_VIN, $job->model_id);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame('success', $job->status);
        $this->assertSame(42, $job->user_id);
        $this->assertSame(['ProductionNo' => self::PRODUCTION_NO, 'TempVIN' => self::OLD_VIN, 'VIN' => self::VIN], $job->parameters);
        $this->assertSame(['rest_erp_success'], $job->actionLogs()->pluck('action')->all());
        $this->assertSame(0, DB::transactionLevel());
        Http::assertSentCount(1);
        Queue::assertNothingPushed();
    }

    public static function failures(): array
    {
        return [
            'business error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'VIN change rejected.']], 400, 'failed'],
            'authentication error' => [[], 401, 'failed'],
            'server error' => [[], 500, 'uncertain'],
            'connection timeout' => [null, 0, 'uncertain'],
        ];
    }

    #[DataProvider('failures')]
    public function test_erp_failure_preserves_the_job_without_local_changes_or_an_open_transaction(?array $body, int $status, string $jobStatus): void
    {
        Http::fake(function () use ($body, $status) {
            $this->assertSame(0, DB::transactionLevel());
            if ($status === 0) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response($body, $status);
        });

        $response = $this->controller(false)->store($this->request());

        $this->assertSame(400, $response->status());
        $this->assertStringContainsString('خطای بیزینس سنترال ::', $response->getData(true)['message']);
        $this->assertNoLocalChanges();
        $job = RestErpJob::query()->sole();
        $this->assertSame($jobStatus, $job->status);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame($jobStatus === 'failed' ? ['rest_erp_failed'] : [], $job->actionLogs()->pluck('action')->all());
        Queue::assertNothingPushed();
    }

    public function test_local_failure_rolls_back_changes_and_retains_the_successful_erp_job(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        DB::statement("CREATE TRIGGER reject_vin_change BEFORE INSERT ON change_vins BEGIN SELECT RAISE(ABORT, 'Local save failed'); END");

        $response = $this->controller(true)->store($this->request());

        $this->assertSame(500, $response->status());
        $this->assertNoLocalChanges();
        $job = RestErpJob::query()->sole();
        $this->assertSame('success', $job->status);
        $this->assertSame(['rest_erp_success'], $job->actionLogs()->pluck('action')->all());
        Http::assertSentCount(1);
    }

    public function test_missing_line_entry_does_not_call_erp_or_leave_an_open_transaction(): void
    {
        DB::table('production_tracking')->delete();

        $response = $this->controller(false)->store($this->request());

        $this->assertSame(422, $response->status());
        $this->assertSame('شماره وارد شده ورود به خط ندارد', $response->getData(true)['message']);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Http::assertNothingSent();
    }

    public function test_a_nullable_old_vin_preserves_the_existing_request_contract(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $response = $this->controller(true)->store($this->request(['old_vin' => null]));

        $this->assertInstanceOf(ChangeVinResource::class, $response);
        $this->assertNull(ChangeVin::query()->sole()->old_vin);
        $this->assertSame('', RestErpJob::query()->sole()->parameters['TempVIN']);
        $this->assertSame(0, DB::transactionLevel());
        Http::assertSentCount(1);
    }

    private function assertNoLocalChanges(): void
    {
        $this->assertDatabaseCount('change_vins', 0);
        $this->assertDatabaseCount('production_tracking', 1);
        $this->assertDatabaseMissing('vehicles', ['vin' => self::VIN]);
        $this->assertDatabaseHas('vin_engines', ['vin' => self::VIN, 'production_order_number' => 'OLD-PO']);
        $this->assertSame(0, DB::transactionLevel());
    }
}
