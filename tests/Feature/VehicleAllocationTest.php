<?php

namespace Tests\Feature;

use App\Http\Controllers\VehicleController;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAllocation;
use App\Services\Erp\ErpOdataClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VehicleAllocationTest extends TestCase
{
    private const ERP_URL = 'http://erp.test/ODataV4/Company(\'Pars\')/SubmitVehicleAllocation';

    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'cache.default' => 'array',
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'erp.vehicle_allocation' => self::ERP_URL,
            'erp.vehicles' => 'http://erp.test/vehicles',
            'erp.make_codes' => 'http://erp.test/make-codes',
            'erp.items' => 'http://erp.test/items',
            'erp.auth' => ['test-user', 'test-password', 'ntlm'],
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
        foreach ([
            '2023_10_29_103259_create_permission_tables.php',
            '2023_10_31_101331_create_make_codes_table.php',
            '2023_10_31_102012_create_make_code_user_table.php',
            '2023_10_31_110719_create_car_models_table.php',
            '2026_09_07_133553_create_car_model_locations_table.php',
            '2023_10_31_152617_create_vehicles_table.php',
            '2024_05_19_145038_alter_color_code_to_vehicle_table.php',
            '2024_07_20_113329_alter_make_code_id_table.php',
            '2024_09_17_130812_alter_vehicles_table.php',
            '2024_11_26_154036_create_color_maps_table.php',
            '2024_11_27_091937_create_year_maps_table.php',
            '2026_11_07_000000_create_vehicle_allocations_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        DB::table('users')->insert(['id' => 1, 'name' => 'Allocation operator']);
        DB::table('make_codes')->insert(['id' => 3, 'reference' => 'LOCAL-MAKE', 'title' => 'Local make']);
        DB::table('car_models')->insert(['id' => 7, 'reference' => 'LOCAL-MODEL', 'title' => 'Local model']);
        $this->actingAs(User::findOrFail(1));
        auth()->user()->givePermissionTo(Permission::create(['name' => 'vehicle-allocations', 'guard_name' => 'web']));
    }

    public function test_index_only_lists_allocations_for_the_users_assigned_make_codes(): void
    {
        $allowed = $this->createIndexAllocation('ALLOWED-VIN', 3);
        DB::table('make_codes')->insert(['id' => 4, 'reference' => 'OTHER', 'title' => 'Other make']);
        $this->createIndexAllocation('OTHER-VIN', 4);
        DB::table('users')->insert(['id' => 2, 'name' => 'Other operator']);
        User::findOrFail(2)->makeCodes()->attach(4);
        User::findOrFail(1)->makeCodes()->attach(3);

        $this->getJson('/api/vehicle-allocations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $allowed->id)
            ->assertJsonPath('data.0.make_code.reference', 'LOCAL-MAKE')
            ->assertJsonPath('data.0.car_model.reference', 'LOCAL-MODEL')
            ->assertJsonPath('data.0.due_date', '2026-08-19')
            ->assertJsonPath('data.0.is_complete', false)
            ->assertJsonPath('data.0.data', ['SourceNo' => 'SOURCE'])
            ->assertJsonPath('data.0.erp_response', null)
            ->assertJsonPath('data.0.user', null);

        $this->getJson('/api/vehicle-allocations?make_code_id=4')->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_index_filters_all_supported_fields_and_combines_them(): void
    {
        $matching = $this->createIndexAllocation('MATCH-VIN', 3);
        $matching->forceFill(['user_id' => 1, 'created_at' => '2026-08-19 23:59:59'])->save();
        $other = $this->createIndexAllocation('OTHER-VIN', 3);
        DB::table('car_models')->insert(['id' => 8, 'reference' => 'SECOND', 'title' => 'Second model']);
        DB::table('make_codes')->insert(['id' => 4, 'reference' => 'SECOND', 'title' => 'Second make']);
        $other->forceFill([
            'is_complete' => true, 'variant_code' => 'VAR-BLACK',
            'due_date' => '2026-08-20', 'created_at' => '2026-08-20 00:00:00',
            'car_model_id' => 8, 'make_code_id' => 4,
        ])->save();
        DB::table('vehicles')->where('vin', 'MATCH-VIN')->update(['engine_no' => 'ENGINE-1', 'cabin_no' => 'CABIN-1']);
        User::findOrFail(1)->makeCodes()->attach([3, 4]);

        $filters = [
            'id' => $matching->id,
            'vin' => 'MATCH',
            'production_order_number' => 'PO-MATCH-VIN',
            'variant_code' => 'VAR-SILVER',
            'make_code_id' => 3,
            'car_model_id' => 7,
            'user_id' => 1,
            'is_complete' => 0,
            'engine_no' => 'ENGINE-1',
            'cabin_no' => 'CABIN-1',
            'due_date' => '2026-08-19',
            'due_date_to' => '2026-08-19',
            'created_at_to' => '2026-08-19',
        ];
        foreach ($filters as $field => $value) {
            $response = $this->getJson('/api/vehicle-allocations?'.http_build_query([$field => $value]))->assertOk();
            $this->assertCount(1, $response->json('data'), $field);
            $response->assertJsonPath('data.0.id', $matching->id);
        }
        $this->getJson('/api/vehicle-allocations?'.http_build_query($filters))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $matching->id);

        foreach ([
            ['is_complete' => 1],
            ['due_date_from' => '2026-08-20'],
            ['created_at_from' => '2026-08-20'],
        ] as $filter) {
            $this->getJson('/api/vehicle-allocations?'.http_build_query($filter))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $other->id);
        }
        $this->getJson('/api/vehicle-allocations?per_page=1&is_complete=0')
            ->assertOk()->assertJsonPath('links.first', url('/api/vehicle-allocations').'?per_page=1&is_complete=0&page=1');
    }

    public function test_index_rejects_invalid_filters(): void
    {
        foreach ([
            ['is_complete' => 'invalid'],
            ['make_code_id' => [3]],
            ['car_model_id' => 'invalid'],
            ['user_id' => -1],
            ['vin' => ['VIN']],
            ['due_date' => '2026-02-30'],
            ['due_date_from' => '2026-08-20', 'due_date_to' => '2026-08-19'],
            ['created_at_from' => '2026-08-20', 'created_at_to' => '2026-08-19'],
        ] as $filters) {
            $this->getJson('/api/vehicle-allocations?'.http_build_query($filters))->assertUnprocessable();
        }
    }

    public function test_index_returns_no_allocations_without_assigned_make_codes(): void
    {
        $this->createIndexAllocation('UNASSIGNED-VIN', 3);

        $this->getJson('/api/vehicle-allocations')->assertOk()
            ->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);
    }

    public function test_index_paginates_multiple_assigned_make_codes_in_descending_id_order(): void
    {
        $this->createIndexAllocation('FIRST-VIN', 3);
        DB::table('make_codes')->insert(['id' => 4, 'reference' => 'OTHER', 'title' => 'Other make']);
        $latest = $this->createIndexAllocation('LATEST-VIN', 4);
        $latest->update(['user_id' => 1, 'is_complete' => true]);
        User::findOrFail(1)->makeCodes()->attach([3, 4]);

        $this->getJson('/api/vehicle-allocations?per_page=1')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.id', $latest->id)
            ->assertJsonPath('data.0.user.name', 'Allocation operator')
            ->assertJsonPath('data.0.is_complete', true);
        $this->getJson('/api/vehicle-allocations?per_page=1&page=2')->assertOk()
            ->assertJsonPath('data.0.vin', 'FIRST-VIN');
    }

    public function test_index_validates_page_size(): void
    {
        foreach ([0, -1, 101, 'invalid'] as $perPage) {
            $this->getJson('/api/vehicle-allocations?per_page='.$perPage)
                ->assertUnprocessable()->assertJsonValidationErrors('per_page');
        }
    }

    public function test_index_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/vehicle-allocations')->assertUnauthorized();
    }

    private function createIndexAllocation(string $vin, int $makeCodeId): VehicleAllocation
    {
        $this->createVehicle($vin, 'PO-'.$vin);
        DB::table('vehicles')->where('vin', $vin)->update(['make_code_id' => $makeCodeId]);

        return VehicleAllocation::query()->create([
            'vin' => $vin,
            'data' => ['SourceNo' => 'SOURCE'],
            'production_order_number' => 'PO-'.$vin,
            'variant_code' => 'VAR-SILVER',
            'car_model_id' => 7,
            'make_code_id' => $makeCodeId,
            'due_date' => '2026-08-19',
        ]);
    }

    public function test_imports_the_sample_using_vehicle_foreign_keys_and_configured_odata_connection(): void
    {
        $this->createVehicle();
        $record = $this->record();
        $record['ExtraData'] = ['nested' => ['value' => 42]];
        $this->fakeOdata([$this->page([$record])]);

        $this->postJson('/api/vehicle-allocations', ['vin' => 'IGNORED'])
            ->assertOk()
            ->assertJsonPath('data', ['total' => 1, 'created' => 1, 'updated' => 0, 'skipped' => 0, 'skipped_vins' => [], 'completed' => 0]);

        $allocation = VehicleAllocation::query()->sole();
        $this->assertSame($record['VINNo'], $allocation->vin);
        $this->assertSame('05PRO003347', $allocation->production_order_number);
        $this->assertSame('2026-08-19', $allocation->due_date->format('Y-m-d'));
        $this->assertSame('VAR-SILVER', $allocation->variant_code);
        $this->assertSame(7, $allocation->car_model_id);
        $this->assertSame(3, $allocation->make_code_id);
        $this->assertSame($record, $allocation->data);
        $this->assertFalse($allocation->is_complete);
        $this->assertNull($allocation->erp_response);
        $this->assertNull($allocation->user_id);
        $this->assertTrue($allocation->vehicle->is(Vehicle::findOrFail($record['VINNo'])));
        $this->assertTrue($allocation->vehicle->vehicleAllocation->is($allocation));
        $this->assertSame(7, $allocation->carModel->id);
        $this->assertSame(3, $allocation->makeCode->id);
        $this->assertCount(1, $this->history);
        $this->assertSame(self::ERP_URL, (string) $this->history[0]['request']->getUri());
        $this->assertSame('GET', $this->history[0]['request']->getMethod());
        $this->assertSame(config('erp.auth'), $this->history[0]['options']['auth']);
    }

    public function test_command_imports_without_an_authenticated_user_and_updates_by_vin(): void
    {
        $this->app['auth']->forgetGuards();
        $this->createVehicle();
        $changed = $this->record(['VariantCode' => 'VAR-BLACK']);
        $this->fakeOdata([$this->page([$this->record()]), $this->page([$changed])]);

        $this->artisan('app:sync-vehicle-allocations')
            ->expectsTable(['Metric', 'Count'], [
                ['Allocations received', 1],
                ['Allocations created', 1],
                ['Existing allocations updated', 0],
                ['Allocations skipped', 0],
                ['Absent allocations marked complete', 0],
            ])
            ->expectsOutputToContain('Vehicle allocation synchronization completed successfully.')
            ->assertExitCode(0);
        $this->artisan('app:sync-vehicle-allocations')
            ->expectsTable(['Metric', 'Count'], [
                ['Allocations received', 1],
                ['Allocations created', 0],
                ['Existing allocations updated', 1],
                ['Allocations skipped', 0],
                ['Absent allocations marked complete', 0],
            ])
            ->expectsOutputToContain('Vehicle allocation synchronization completed successfully.')
            ->assertExitCode(0);

        $allocation = VehicleAllocation::query()->sole();
        $this->assertSame($changed, $allocation->data);
        $this->assertSame(7, $allocation->car_model_id);
        $this->assertSame(3, $allocation->make_code_id);
        $this->assertNull($allocation->user_id);
        $this->assertCount(2, $this->history);
    }

    public function test_command_returns_failure_for_an_erp_error_without_writing(): void
    {
        $this->fakeOdata([new Response(503)]);

        $this->artisan('app:sync-vehicle-allocations')
            ->expectsOutputToContain('Vehicle allocation synchronization failed.')
            ->expectsOutput('دریافت اطلاعات تخصیص خودرو از ERP ناموفق بود یا پاسخ OData معتبر نیست.')
            ->assertExitCode(1);

        $this->assertSame(0, VehicleAllocation::count());
    }

    public function test_command_reports_unknown_vins_and_continues_with_existing_vehicles(): void
    {
        $this->createVehicle();
        $missingVin = 'N5RT92CF6TP999999';
        $this->fakeOdata([
            $this->page([$this->record(['VINNo' => $missingVin]), $this->record()]),
            $this->page([]),
        ]);

        $this->artisan('app:sync-vehicle-allocations')
            ->expectsTable(['Metric', 'Count'], [
                ['Allocations received', 2],
                ['Allocations created', 1],
                ['Existing allocations updated', 0],
                ['Allocations skipped', 1],
                ['Absent allocations marked complete', 0],
            ])
            ->expectsOutputToContain('Synchronization completed with skipped allocations.')
            ->expectsTable(['Skipped vehicle VIN'], [[$missingVin]])
            ->assertExitCode(0);

        $this->assertSame($this->record()['VINNo'], VehicleAllocation::query()->sole()->vin);
        $this->assertCount(2, $this->history);
    }

    public function test_command_waits_for_get_data_to_import_vehicle_and_related_records_before_allocation(): void
    {
        $this->app['auth']->forgetGuards();
        $record = $this->record();
        $erpVehicle = [
            'VIN' => $record['VINNo'],
            'ProductionOrder' => $record['ProductionOrderNo'],
            'ColorCode' => $record['VariantCode'],
            'EngineNo' => 'ENGINE-123',
            'CabinNo' => 'CABIN-123',
            'MakeCode' => 'ERP-MAKE',
            'ModelNo' => 'ERP-MODEL',
            'Model_Year' => 1405,
            'Blocked' => false,
        ];
        $this->fakeOdata([
            $this->page([$record]),
            $this->page([$erpVehicle]),
            $this->page([['Code' => 'ERP-MAKE']]),
            $this->page([['No' => 'ERP-MODEL']]),
        ]);

        $this->artisan('app:sync-vehicle-allocations')
            ->expectsOutputToContain('Vehicle allocation synchronization completed successfully.')
            ->assertExitCode(0);

        $vehicle = Vehicle::query()->withoutEagerLoads()->sole();
        $allocation = VehicleAllocation::query()->sole();
        $this->assertSame($record['VINNo'], $vehicle->vin);
        $this->assertSame($erpVehicle, $vehicle->data);
        $this->assertSame('ENGINE-123', $vehicle->engine_no);
        $this->assertSame($vehicle->car_model_id, $allocation->car_model_id);
        $this->assertSame($vehicle->make_code_id, $allocation->make_code_id);
        $this->assertSame('ERP-MODEL', $vehicle->carModel->reference);
        $this->assertSame('ERP-MAKE', $vehicle->makeCode->reference);
        $this->assertSame($vehicle->make_code_id, $vehicle->carModel->make_code_id);
        $this->assertDatabaseHas('color_maps', ['color_code' => 'VAR-SILVER']);
        $this->assertDatabaseHas('year_maps', ['year' => 1405]);
        $this->assertNull($allocation->user_id);
        $this->assertCount(4, $this->history);
        $this->assertSame("http://erp.test/vehicles?\$filter=VIN eq '".$record['VINNo']."'", rawurldecode((string) $this->history[1]['request']->getUri()));
    }

    public function test_command_is_scheduled_every_ten_minutes_without_overlapping(): void
    {
        $events = collect($this->app->make(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'app:sync-vehicle-allocations'));

        $this->assertCount(1, $events);
        $this->assertSame('*/10 * * * *', $events->first()->expression);
        $this->assertTrue($events->first()->withoutOverlapping);
    }

    public function test_command_completes_only_allocations_absent_from_all_pages_and_preserves_other_fields(): void
    {
        $this->createVehicle();
        $this->createVehicle('SECOND-VIN', 'SECOND-PO');
        $this->fakeOdata([
            $this->page([$this->record(), $this->record(['VINNo' => 'SECOND-VIN'])]),
            $this->page([], '?$skiptoken=next'),
            $this->page([$this->record()]),
            $this->page([$this->record()]),
        ]);
        $this->artisan('app:sync-vehicle-allocations')->assertExitCode(0);
        $absent = VehicleAllocation::where('vin', 'SECOND-VIN')->firstOrFail();
        $absent->update(['erp_response' => ['success' => true], 'user_id' => 1]);
        $original = $absent->getAttributes();

        $this->artisan('app:sync-vehicle-allocations')
            ->expectsTable(['Metric', 'Count'], [
                ['Allocations received', 1],
                ['Allocations created', 0],
                ['Existing allocations updated', 1],
                ['Allocations skipped', 0],
                ['Absent allocations marked complete', 1],
            ])
            ->assertExitCode(0);

        $this->assertTrue($absent->refresh()->is_complete);
        foreach (['vin', 'data', 'production_order_number', 'due_date', 'variant_code', 'car_model_id', 'make_code_id', 'erp_response', 'user_id'] as $field) {
            $this->assertSame($original[$field], $absent->getAttributes()[$field]);
        }
        $this->assertFalse(VehicleAllocation::where('vin', $this->record()['VINNo'])->firstOrFail()->is_complete);

        $this->postJson('/api/vehicle-allocations')->assertOk()->assertJsonPath('data.completed', 0);
    }

    public function test_returned_but_skipped_allocation_is_not_marked_complete(): void
    {
        $this->createVehicle();
        $this->fakeOdata([$this->page([$this->record()]), $this->page([$this->record()])]);
        $this->artisan('app:sync-vehicle-allocations')->assertExitCode(0);
        Vehicle::query()->firstOrFail()->delete();

        $this->artisan('app:sync-vehicle-allocations')->assertExitCode(0);

        $this->assertFalse(VehicleAllocation::query()->sole()->is_complete);
    }

    public function test_failed_or_invalid_feed_does_not_complete_existing_allocations(): void
    {
        $this->createVehicle();
        $this->fakeOdata([
            $this->page([$this->record()]),
            $this->page([], '?$skiptoken=next'),
            new Response(503),
            $this->page([$this->record(['VINNo' => null])]),
        ]);
        $this->artisan('app:sync-vehicle-allocations')->assertExitCode(0);

        $this->artisan('app:sync-vehicle-allocations')->assertExitCode(1);
        $this->assertFalse(VehicleAllocation::query()->sole()->is_complete);

        $this->artisan('app:sync-vehicle-allocations')->assertExitCode(1);
        $this->assertFalse(VehicleAllocation::query()->sole()->is_complete);
    }

    public function test_reimport_updates_the_same_vin_and_preserves_completion_response_and_user(): void
    {
        $this->createVehicle();
        $changed = $this->record(['ProductionOrderNo' => 'NEW-PO', 'VariantCode' => 'VAR-BLACK', 'DueDate' => '2026-09-20']);
        $this->fakeOdata([$this->page([$this->record()]), $this->page([$changed])]);
        $this->postJson('/api/vehicle-allocations')->assertOk();
        $allocation = VehicleAllocation::query()->sole();
        $allocation->update(['is_complete' => true, 'erp_response' => ['success' => true], 'user_id' => 1]);

        $this->postJson('/api/vehicle-allocations')->assertOk()
            ->assertJsonPath('data', ['total' => 1, 'created' => 0, 'updated' => 1, 'skipped' => 0, 'skipped_vins' => [], 'completed' => 0]);

        $this->assertSame(1, VehicleAllocation::count());
        $allocation->refresh();
        $this->assertTrue($allocation->is_complete);
        $this->assertSame(['success' => true], $allocation->erp_response);
        $this->assertSame(1, $allocation->user_id);
        $this->assertSame('NEW-PO', $allocation->production_order_number);
        $this->assertSame('VAR-BLACK', $allocation->variant_code);
        $this->assertSame('2026-09-20', $allocation->due_date->format('Y-m-d'));
        $this->assertSame($changed, $allocation->data);
        $this->assertSame(1, $allocation->user->id);
    }

    public function test_follows_relative_and_absolute_odata_pagination_links(): void
    {
        $this->createVehicle();
        $this->createVehicle('SECOND-VIN', 'SECOND-PO');
        $this->fakeOdata([
            $this->page([$this->record()], '?$skiptoken=second'),
            $this->page([$this->record(['VINNo' => 'SECOND-VIN', 'ProductionOrderNo' => 'SECOND-PO'])], self::ERP_URL.'?$skiptoken=third'),
            $this->page([]),
        ]);

        $this->postJson('/api/vehicle-allocations')->assertOk()->assertJsonPath('data.created', 2);

        $this->assertCount(3, $this->history);
        $this->assertSame(self::ERP_URL.'?$skiptoken=second', (string) $this->history[1]['request']->getUri());
        $this->assertSame(self::ERP_URL.'?$skiptoken=third', (string) $this->history[2]['request']->getUri());
    }

    public function test_unknown_vehicle_is_skipped_after_erp_lookup_without_rejecting_other_allocations(): void
    {
        $this->createVehicle();
        $missingVin = 'N5RT92CF6TP999999';
        $this->fakeOdata([
            $this->page([$this->record(['VINNo' => $missingVin]), $this->record()]),
            $this->page([]),
        ]);

        $this->postJson('/api/vehicle-allocations')->assertOk()
            ->assertJsonPath('data', ['total' => 2, 'created' => 1, 'updated' => 0, 'skipped' => 1, 'skipped_vins' => [$missingVin], 'completed' => 0]);

        $this->assertSame($this->record()['VINNo'], VehicleAllocation::query()->sole()->vin);
        $this->assertSame(1, Vehicle::count());
        $this->assertCount(2, $this->history);
    }

    public function test_soft_deleted_vehicle_cannot_be_imported(): void
    {
        $this->createVehicle();
        Vehicle::query()->firstOrFail()->delete();
        $this->fakeOdata([$this->page([$this->record()])]);

        $this->postJson('/api/vehicle-allocations')->assertOk()
            ->assertJsonPath('data.skipped_vins', [$this->record()['VINNo']]);
        $this->assertSame(0, VehicleAllocation::count());
        $this->assertCount(1, $this->history);
    }

    public function test_a_get_data_failure_rolls_back_its_writes_and_is_not_reported_as_a_missing_vehicle(): void
    {
        $this->fakeOdata([$this->page([$this->record()])]);
        $controller = $this->createMock(VehicleController::class);
        $controller->expects($this->once())->method('getData')->with($this->record()['VINNo'])
            ->willReturnCallback(function () {
                DB::table('make_codes')->insert(['reference' => 'PARTIAL-MAKE', 'title' => 'Partial make']);
                throw new RuntimeException('Simulated database failure');
            });
        $this->app->instance(VehicleController::class, $controller);

        $this->artisan('app:sync-vehicle-allocations')
            ->expectsOutputToContain('Vehicle allocation synchronization failed.')
            ->expectsOutput('Simulated database failure')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('make_codes', ['reference' => 'PARTIAL-MAKE']);
        $this->assertSame(0, VehicleAllocation::count());
    }

    public function test_get_data_must_save_the_requested_vin_before_an_allocation_is_created(): void
    {
        $this->fakeOdata([$this->page([$this->record()])]);
        $controller = $this->createMock(VehicleController::class);
        $controller->expects($this->once())->method('getData')->with($this->record()['VINNo'])
            ->willReturnCallback(function () {
                $this->createVehicle('DIFFERENT-VIN');

                return Vehicle::query()->withoutEagerLoads()->findOrFail('DIFFERENT-VIN');
            });
        $this->app->instance(VehicleController::class, $controller);

        $this->postJson('/api/vehicle-allocations')->assertOk()
            ->assertJsonPath('data.skipped', 1)
            ->assertJsonPath('data.skipped_vins', [$this->record()['VINNo']]);

        $this->assertSame(0, Vehicle::count());
        $this->assertSame(0, VehicleAllocation::count());
    }

    public static function invalidRecords(): array
    {
        return [
            'missing vin' => [['VINNo' => null], 'records.0.VINNo'],
            'invalid production order' => [['ProductionOrderNo' => 123], 'records.0.ProductionOrderNo'],
            'invalid calendar date' => [['DueDate' => '2026-02-30'], 'records.0.DueDate'],
            'invalid date format' => [['DueDate' => '19/08/2026'], 'records.0.DueDate'],
            'missing variant' => [['VariantCode' => null], 'records.0.VariantCode'],
        ];
    }

    #[DataProvider('invalidRecords')]
    public function test_invalid_records_are_rejected_before_writing(array $overrides, string $errorKey): void
    {
        $this->createVehicle();
        $this->fakeOdata([$this->page([$this->record($overrides)])]);

        $this->postJson('/api/vehicle-allocations')->assertUnprocessable()->assertJsonValidationErrors($errorKey);

        $this->assertSame(0, VehicleAllocation::count());
    }

    public function test_conflicting_allocations_for_the_same_vin_are_rejected(): void
    {
        $this->createVehicle();
        $this->fakeOdata([$this->page([$this->record(), $this->record(['ProductionOrderNo' => 'OTHER-PO'])])]);

        $this->postJson('/api/vehicle-allocations')->assertUnprocessable()->assertJsonValidationErrors('records.0.VINNo');
        $this->assertSame(0, VehicleAllocation::count());
    }

    public static function invalidResponses(): array
    {
        return [
            'http error' => [new Response(500, [], '{"error":{"message":"ERP failure"}}')],
            'invalid json' => [new Response(200, [], 'not json')],
            'missing value' => [new Response(200, [], '{}')],
            'invalid value' => [new Response(200, [], '{"value":{}}')],
            'connection error' => [new ConnectException('Connection timed out', new Request('GET', self::ERP_URL))],
        ];
    }

    #[DataProvider('invalidResponses')]
    public function test_erp_errors_are_reported_as_bad_gateway(mixed $response): void
    {
        $this->fakeOdata([$response]);

        $this->postJson('/api/vehicle-allocations')->assertStatus(502);
        $this->assertSame(0, VehicleAllocation::count());
    }

    public function test_a_later_page_failure_does_not_save_the_first_page(): void
    {
        $this->createVehicle();
        $this->fakeOdata([$this->page([$this->record()], '?$skiptoken=next'), new Response(503)]);

        $this->postJson('/api/vehicle-allocations')->assertStatus(502);
        $this->assertSame(0, VehicleAllocation::count());
    }

    public function test_empty_feed_completes_existing_allocations_without_deleting_them(): void
    {
        $this->createVehicle();
        $this->fakeOdata([$this->page([$this->record()]), $this->page([])]);
        $this->postJson('/api/vehicle-allocations')->assertOk();

        $this->postJson('/api/vehicle-allocations')->assertOk()
            ->assertJsonPath('data', ['total' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'skipped_vins' => [], 'completed' => 1]);
        $this->assertSame(1, VehicleAllocation::count());
        $this->assertTrue(VehicleAllocation::query()->sole()->is_complete);
    }

    public function test_cross_origin_next_link_is_rejected_before_sending_credentials(): void
    {
        $this->fakeOdata([$this->page([], 'http://other.test/odata')]);

        $this->postJson('/api/vehicle-allocations')->assertStatus(502);
        $this->assertCount(1, $this->history);
    }

    public function test_repeated_pagination_link_is_rejected_without_looping(): void
    {
        $this->fakeOdata([$this->page([], self::ERP_URL)]);

        $this->postJson('/api/vehicle-allocations')->assertStatus(502);
        $this->assertCount(1, $this->history);
    }

    public function test_authentication_is_required_without_contacting_erp(): void
    {
        $this->app['auth']->forgetGuards();
        $this->fakeOdata([]);

        $this->postJson('/api/vehicle-allocations')->assertUnauthorized();
        $this->assertCount(0, $this->history);
    }

    public function test_database_rejects_a_duplicate_vin(): void
    {
        $this->createVehicle();
        $this->fakeOdata([$this->page([$this->record()])]);
        $this->postJson('/api/vehicle-allocations')->assertOk();

        $this->expectException(QueryException::class);
        VehicleAllocation::query()->sole()->replicate()->save();
    }

    public function test_database_rejects_an_unknown_vehicle_foreign_key(): void
    {
        $this->createVehicle();
        $this->fakeOdata([$this->page([$this->record()])]);
        $this->postJson('/api/vehicle-allocations')->assertOk();

        $this->expectException(QueryException::class);
        VehicleAllocation::query()->sole()->update(['vin' => 'UNKNOWN-VIN']);
    }

    private function createVehicle(string $vin = 'N5RT92CF6TP000548', string $po = '05PRO003347'): void
    {
        DB::table('vehicles')->insert([
            'vin' => $vin,
            'production_order_number' => $po,
            'car_model_id' => 7,
            'make_code_id' => 3,
            'data' => '{}',
        ]);
    }

    private function record(array $overrides = []): array
    {
        return array_replace([
            'ProductionOrderNo' => '05PRO003347',
            'DueDate' => '2026-08-19',
            'SourceNo' => 'KSPCO010CP',
            'VariantCode' => 'VAR-SILVER',
            'Make' => 'LAMARI',
            'VINNo' => 'N5RT92CF6TP000548',
            'ItemType' => 'Vehicle',
            'FinishedQuantity' => 1,
            'AuxiliaryIndex1' => 'Released',
            'AuxiliaryIndex2' => 'Released',
            'AuxiliaryIndex3' => '05PRO003347',
            'AuxiliaryIndex4' => 10000,
        ], $overrides);
    }

    private function page(array $records, ?string $nextLink = null): Response
    {
        $body = ['value' => $records];
        if ($nextLink !== null) {
            $body['@odata.nextLink'] = $nextLink;
        }

        return new Response(200, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }

    private function fakeOdata(array $responses): void
    {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));
        $this->app->instance(ErpOdataClient::class, new ErpOdataClient(new Client(['handler' => $handler])));
    }
}
