<?php

namespace Tests\Feature;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Http\Controllers\VehicleController;
use App\Imports\BillOfLadingImport;
use App\Models\BillOfLading;
use App\Models\Container;
use App\Models\Job;
use App\Models\Vehicle;
use App\Services\CreateAutoGatePermitService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class BillOfLadingImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Event::fake([JobProcessEvent::class]);
        $this->mock(VehicleController::class)->shouldNotReceive('getByVin');

        Schema::create('custom_jobs', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->integer('user_id');
            $table->json('data');
            $table->json('result')->nullable();
            $table->timestamps();
        });
        foreach ([
            'drivers' => ['national_code', 'phone', 'name'],
            'transit_companies' => ['title'],
            'cars' => ['plate', 'car', 'transit_company_id', 'driver_id'],
            'containers' => ['code'],
            'vehicles' => ['VIN'],
            'bill_of_ladings' => ['user_id', 'location_source_id', 'location_destination_id', 'car_id', 'code', 'request_no', 'status', 'priority', 'cottage', 'weight', 'batch', 'description'],
            'bill_of_lading_item' => ['model_id', 'model_type', 'bill_of_lading_id', 'type'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    private function importer(string $type = 'vehicles', string|int $itemType = ''): BillOfLadingImport
    {
        $job = Job::query()->create([
            'user_id' => 42,
            'data' => ['type' => $type, 'item_type' => $itemType, 'location_source_id' => 10, 'location_destination_id' => 20],
        ]);

        return new BillOfLadingImport([
            'id' => $job->getKey(), 'user_id' => 42, 'total' => 0, 'done' => 0, 'success' => 0, 'error' => 0,
        ]);
    }

    private function row(BillOfLadingImport $import, array $changes = []): Collection
    {
        return collect($import->map(array_values(array_replace([
            'item' => 'VIN-1', 'shipping_company' => 'Carrier', 'vehicle_title' => 'Truck',
            'plate_area' => '11', '3_digit_plate' => '123', 'char_plate' => 'ب', '2_digit_plate' => '45',
            'driver_name' => 'Driver', 'driver_mobile' => '09120000000', 'driver_national_code' => '1234567890',
            'freight_number' => 'F-1', 'request_number' => 'R-1', 'cottage_number' => 'C-1',
            'weight' => 100, 'batch' => 'B-1', 'description' => 'Test import',
        ], $changes))));
    }

    public static function importTypes(): array
    {
        return [['vehicles', Vehicle::class], ['bodies', Vehicle::class], ['containers', Container::class]];
    }

    #[DataProvider('importTypes')]
    public function test_new_and_existing_bill_rows_preserve_counts_items_and_report(string $type, string $model): void
    {
        $import = $this->importer($type);
        DB::table('vehicles')->insert([['VIN' => 'VIN-1'], ['VIN' => 'VIN-2']]);
        $rows = collect([$this->row($import), $this->row($import, ['item' => 'VIN-2'])]);
        Excel::shouldReceive('store')->once()->withArgs(function (ResultExport $export, string $path) use ($rows, $type) {
            $this->assertSame($type === 'containers' ? BillOfLading::HEAD_CONTAINER : BillOfLading::HEAD_VEHICLE, $export->collection()->first());
            $this->assertSame('imported', $rows[1]['result']);
            $this->assertFalse($rows[2]->has('result'));
            $this->assertStringStartsWith('public/results/', $path);

            return true;
        })->andReturn(true);

        $import->collection($rows);

        $this->assertDatabaseCount('bill_of_ladings', 1);
        $this->assertDatabaseHas('bill_of_ladings', ['user_id' => 42, 'location_source_id' => 10, 'location_destination_id' => 20, 'status' => 'new', 'priority' => 0]);
        $this->assertDatabaseCount('drivers', 1);
        $this->assertDatabaseCount('transit_companies', 1);
        $this->assertDatabaseCount('cars', 1);
        $this->assertDatabaseCount('bill_of_lading_item', 2);
        $this->assertSame([$type, $type], DB::table('bill_of_lading_item')->pluck('type')->all());
        $this->assertSame([$model, $model], DB::table('bill_of_lading_item')->pluck('model_type')->all());
        $this->assertSame([2, 2, 1, 0], array_map(fn ($key) => $import->data[$key], ['total', 'done', 'success', 'error']));
        $result = Job::query()->findOrFail($import->data['id'])->getAttribute('result');
        $this->assertSame(1, $result['success']);
        $this->assertSame(2, $result['total']);
        $this->assertStringContainsString('results/', urldecode($result['file']));
        Event::assertDispatchedTimes(JobProcessEvent::class, 3);
        if ($type === 'bodies') {
            $this->assertSame(['VIN-1', 'VIN-2'], array_values($import->billOfLadingList)[0]['vin']);
        }
    }

    public static function unavailableVehicles(): array
    {
        return ['missing vehicle' => [false], 'soft deleted vehicle' => [true]];
    }

    #[DataProvider('unavailableVehicles')]
    public function test_body_import_uses_import_type_when_previous_vehicle_is_not_visible(bool $softDeleted): void
    {
        $import = $this->importer('bodies');
        if ($softDeleted) {
            DB::table('vehicles')->insert(['VIN' => 'VIN-1', 'deleted_at' => now()]);
        } else {
            $this->mock(VehicleController::class)->shouldReceive('getByVin')->once()->with('VIN-1')
                ->andReturnUsing(function () {
                    DB::table('vehicles')->insert(['VIN' => 'VIN-1']);

                    return new JsonResponse(['message' => 'You are not authorized to access this vehicle'], 403);
                });
        }
        DB::table('vehicles')->insert(['VIN' => 'VIN-2']);
        $rows = collect([$this->row($import), $this->row($import, ['item' => 'VIN-2'])]);
        Excel::shouldReceive('store')->once()->andReturn(true);

        $import->collection($rows);

        $this->assertSame(['bodies', 'bodies'], DB::table('bill_of_lading_item')->orderBy('model_id')->pluck('type')->all());
        $this->assertSame(['VIN-1', 'VIN-2'], array_values($import->billOfLadingList)[0]['vin']);
        $this->assertSame(0, $import->data['error']);
    }

    public function test_missing_vin_is_fetched_once_and_existing_vin_is_not_fetched(): void
    {
        $import = $this->importer('bodies');
        DB::table('vehicles')->insert(['VIN' => 'VIN-2']);
        $this->mock(VehicleController::class)->shouldReceive('getByVin')->once()->with('VIN-1')
            ->andReturnUsing(function () {
                DB::table('vehicles')->insert(['VIN' => 'VIN-1']);

                return new JsonResponse(['message' => 'Saved']);
            });
        $rows = collect([$this->row($import), $this->row($import), $this->row($import, ['item' => 'VIN-2'])]);
        Excel::shouldReceive('store')->once()->andReturn(true);

        $import->collection($rows);

        $this->assertDatabaseCount('vehicles', 2);
        $this->assertDatabaseCount('bill_of_lading_item', 3);
        $this->assertSame(0, $import->data['error']);
        $this->assertSame(3, $import->data['done']);
    }

    public static function failedLookups(): array
    {
        return ['error response' => ['response'], 'no saved vehicle' => ['empty'], 'exception' => ['exception']];
    }

    #[DataProvider('failedLookups')]
    public function test_failed_lookup_is_not_repeated_and_does_not_register_the_missing_vin(string $failure): void
    {
        $import = $this->importer('bodies');
        DB::table('vehicles')->insert(['VIN' => 'VIN-2']);
        $lookup = $this->mock(VehicleController::class)->shouldReceive('getByVin')->once()->with('VIN-1');
        if ($failure === 'exception') {
            $lookup->andThrow(new RuntimeException('ERP unavailable'));
        } else {
            $lookup->andReturn($failure === 'response'
                ? new JsonResponse(['message' => 'ERP unavailable'], 400)
                : new JsonResponse([]));
        }
        $rows = collect([$this->row($import), $this->row($import), $this->row($import, ['item' => 'VIN-2'])]);
        Excel::shouldReceive('store')->once()->andReturn(true);

        $import->collection($rows);

        $this->assertSame($failure === 'empty' ? 'Vehicle not found after ERP lookup: VIN-1' : 'ERP unavailable', $rows[1]['result']);
        $this->assertSame($rows[1]['result'], $rows[2]['result']);
        $this->assertSame(2, $import->data['error']);
        $this->assertSame(3, $import->data['done']);
        $this->assertDatabaseCount('bill_of_lading_item', 1);
        $this->assertDatabaseHas('bill_of_lading_item', ['model_id' => 'VIN-2', 'type' => 'bodies']);
        $this->assertSame(['VIN-2'], array_values($import->billOfLadingList)[0]['vin']);
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function gatePermitItemTypes(): array
    {
        $cases = [];
        foreach (['bodies', 'vehicles'] as $type) {
            foreach (['0', 0, '1', '2'] as $index => $itemType) {
                $cases[$type.' item type '.$index] = [$type, $itemType];
            }
        }

        return $cases;
    }

    #[DataProvider('gatePermitItemTypes')]
    public function test_import_passes_all_vins_of_each_bill_to_the_batch_service(string $type, string|int $itemType): void
    {
        $import = $this->importer($type, $itemType);
        DB::table('vehicles')->insert([['VIN' => 'VIN-1'], ['VIN' => 'VIN-2']]);
        $rows = collect([$this->row($import), $this->row($import, ['item' => 'VIN-2'])]);
        $this->mock(CreateAutoGatePermitService::class)->shouldReceive('create')->once()
            ->withArgs(function (BillOfLading $bill, array $vins, string $actualItemType, bool $batch) use ($itemType) {
                $this->assertSame('F-1', $bill->code);
                $this->assertSame(['VIN-1', 'VIN-2'], $vins);
                $this->assertSame((string) $itemType, $actualItemType);
                $this->assertTrue($batch);
                $this->assertDatabaseCount('bill_of_lading_item', 2);

                return true;
            });
        Excel::shouldReceive('store')->once()->andReturn(true);

        $import->collection($rows);

        $this->assertSame(0, $import->data['error']);
        $this->assertNotNull(Job::query()->findOrFail($import->data['id'])->getAttribute('result'));
    }

    public function test_row_errors_keep_prior_side_effects_and_continue_processing(): void
    {
        DB::table('vehicles')->insert(['VIN' => 'VIN-1']);
        $import = $this->importer();
        $rows = collect([
            $this->row($import, ['vehicle_title' => '', 'plate_area' => '']),
            $this->row($import, ['char_plate' => '?']),
            $this->row($import, ['freight_number' => 'F-2']),
        ]);
        Excel::shouldReceive('store')->once()->andReturn(true);

        $import->collection($rows);

        $this->assertSame('vehicle_title is empty', $rows[1]['result']);
        $this->assertSame('حرف پلاک نامجاز است', $rows[2]['result']);
        $this->assertSame('imported', $rows[3]['result']);
        $this->assertSame(2, $import->data['error']);
        $this->assertSame(3, $import->data['done']);
        $this->assertDatabaseCount('drivers', 1);
        $this->assertDatabaseCount('transit_companies', 1);
        $this->assertDatabaseCount('bill_of_ladings', 1);
        Event::assertDispatchedTimes(JobProcessEvent::class, 4);
    }

    public function test_export_failure_rolls_back_all_database_changes(): void
    {
        DB::table('vehicles')->insert(['VIN' => 'VIN-1']);
        $import = $this->importer();
        $rows = collect([$this->row($import)]);
        Excel::shouldReceive('store')->once()->andThrow(new RuntimeException('Export failed'));
        Log::shouldReceive('error')->once()->with('Export failed');

        $import->collection($rows);

        $this->assertDatabaseCount('bill_of_ladings', 0);
        $this->assertDatabaseCount('bill_of_lading_item', 0);
        $this->assertDatabaseCount('drivers', 0);
        $this->assertNull(Job::query()->findOrFail($import->data['id'])->getAttribute('result'));
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_gate_permit_failure_rolls_back_before_export(): void
    {
        DB::table('vehicles')->insert(['VIN' => 'VIN-1']);
        $import = $this->importer('vehicles', '2');
        $rows = collect([$this->row($import)]);
        $bill = Mockery::mock(BillOfLading::class);
        $bill->shouldReceive('createAutoGatePermit')->once()->with(['VIN-1'], '2', true)
            ->andReturnUsing(function () use ($import) {
                $this->assertSame(1, $import->data['done']);
                $this->assertDatabaseCount('bill_of_ladings', 1);
                throw new RuntimeException('Permit failed');
            });
        $import->billOfLadingList = [['billOfLading' => $bill, 'vin' => ['VIN-1']]];
        Excel::shouldReceive('store')->never();
        Log::shouldReceive('error')->once()->with('Permit failed');

        $import->collection($rows);

        $this->assertDatabaseCount('bill_of_ladings', 0);
        $this->assertDatabaseCount('bill_of_lading_item', 0);
        $this->assertNull(Job::query()->findOrFail($import->data['id'])->getAttribute('result'));
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_empty_import_reports_progress_and_does_not_export(): void
    {
        $import = $this->importer();
        Excel::shouldReceive('store')->never();
        Log::shouldReceive('error')->once()->with('No Bill Of Lading Found');

        $import->collection(collect());

        Event::assertDispatchedTimes(JobProcessEvent::class, 1);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertNull(Job::query()->findOrFail($import->data['id'])->getAttribute('result'));
    }
}
