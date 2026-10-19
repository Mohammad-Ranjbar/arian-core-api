<?php

namespace Tests\Feature;

use App\Exceptions\RestErpQueueException;
use App\Http\Controllers\GatePermitController;
use App\Http\Controllers\GatePermitTransferController;
use App\Http\Resources\BillOfLadingResource;
use App\Http\Resources\GatePermitResource;
use App\Jobs\SendToRestErpJob;
use App\Models\BillOfLading;
use App\Models\GatePermit;
use App\Models\RestErpJob;
use App\Models\Vehicle;
use App\Services\CreateAutoGatePermitService;
use App\Services\Erp\RestApi\ExitRequestsService;
use App\Services\Erp\RestApi\GroupExitsService;
use App\Services\Erp\RestApi\RestErpJobLinksService;
use App\Services\Erp\RestApi\RestErpJobService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class ExitRequestsIntegrationTest extends TestCase
{
    use CreatesRestErpTables;

    private const VIN = 'N5RT92CF6SP000481';

    private $soap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        $this->soap = Mockery::mock('alias:App\Services\Erp\ErpWsdlClient');
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
        Gate::define('gate-permit-exit', fn () => true);

        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->unsignedBigInteger('car_model_id')->nullable();
            $table->unsignedBigInteger('make_code_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        foreach (['car_models', 'make_codes'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('reference')->nullable();
            });
        }
        Schema::create('bill_of_lading_item', function (Blueprint $table) {
            $table->id();
            $table->string('model_id');
            $table->string('type');
            $table->unsignedBigInteger('bill_of_lading_id');
        });
        foreach ([
            '2023_01_03_110522_create_locations_table.php',
            '2024_01_03_111007_create_location_attributes_table.php',
            '2023_11_18_160413_create_gate_permits_table.php',
            '2024_05_15_105400_alter_location_id_to_gate_permits_table.php',
            '2025_02_11_151315_alter_gate_permits_table.php',
            '2025_05_18_095138_alter_gate_permits_table.php',
            '2023_11_01_134413_create_production_trackings_table.php',
            '2026_09_13_100000_add_soft_deletes_to_production_tracking_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('gate_permits', fn (Blueprint $table) => $table->string('exit_type')->nullable());
        DB::table('make_codes')->insert(['id' => 1, 'reference' => 'TEST']);
        DB::table('vehicles')->insert([['vin' => self::VIN, 'make_code_id' => 1], ['vin' => 'N5RT92CF6SP000482', 'make_code_id' => 1]]);
        DB::table('locations')->insert([
            ['id' => 1, 'title' => 'Source', 'code' => 'SOURCE'],
            ['id' => 2, 'title' => 'Destination', 'code' => 'CT004156'],
        ]);
        DB::table('location_attributes')->insert(['location_id' => 1, 'attribute' => 'autoExitPermit', 'value' => 'true']);
    }

    private function retryRequest(): Request
    {
        $request = Request::create('/api/gate-permits');
        $request->setUserResolver(fn () => Auth::user());

        return $request;
    }

    private function permitRetryUrl(GatePermit $permit): ?string
    {
        $request = $this->retryRequest();
        $resource = (new GatePermitResource($permit))->resolve($request);

        $this->assertArrayNotHasKey('rest_erp_jobs', $resource);

        return $resource['retry_url'];
    }

    private function persistedBill(): BillOfLading
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::table('bill_of_lading_item', fn (Blueprint $table) => $table->string('model_type')->nullable());
        (require database_path('migrations/2023_11_18_144356_create_bill_of_ladings_table.php'))->up();
        DB::table('cars')->insert(['id' => 1]);

        return BillOfLading::query()->create([
            'user_id' => 42, 'location_source_id' => 1, 'location_destination_id' => 2,
            'code' => 'BOL-1', 'status' => 'new', 'car_id' => 1,
        ]);
    }

    private function store(array $overrides = []): mixed
    {
        $request = Request::create('/api/gate-permits', 'POST', array_replace([
            'vin' => self::VIN, 'type' => 'exit', 'exit_type' => '1',
            'location_id' => 2, 'source_id' => 1, 'item_type' => '2',
        ], $overrides));
        $this->app->instance('request', $request);

        return resolve(GatePermitController::class)->store($request);
    }

    private function bill(): BillOfLading
    {
        return new BillOfLading(['user_id' => 42, 'location_source_id' => 1, 'location_destination_id' => 2]);
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::without(['carModel', 'makeCode'])->findOrFail(self::VIN);
    }

    public function test_manual_permit_returns_202_with_job_ids_and_preserves_the_resource(): void
    {
        $response = $this->store();
        $this->assertSame(202, $response->getStatusCode(), json_encode($response->getData(true)));
        $permit = GatePermit::query()->sole();
        $job = RestErpJob::query()->where('endpoint', 'ExitRequests')->sole();
        $print = RestErpJob::query()->where('endpoint', 'ExitPrints')->sole();
        $this->assertSame($job->id, $print->depends_on_id);
        $this->assertSame(['VIN' => self::VIN, 'ItemType' => '2'], $print->parameters);
        $this->assertSame(GatePermit::class, $print->model_type);
        $this->assertSame((string) $permit->id, $print->model_id);
        $this->assertSame('pending', $print->status);
        $this->assertNull($print->dispatched_at);
        $this->assertDatabaseCount('rest_erp_jobs', 2);
        $body = $response->getData(true);
        $this->assertSame($permit->id, $body['data']['id']);
        $this->assertSame(self::VIN, $body['data']['model_id']);
        $this->assertSame($job->id, $body['rest_erp_job_id']);
        $this->assertSame($job->uuid, $body['request_id']);
        $this->assertSame(['VIN' => self::VIN, 'TypeOfExit' => '1', 'Destination' => 'CT004156', 'ItemType' => '2'], $job->parameters);
        $this->assertSame(GatePermit::class, $job->model_type);
        $this->assertSame((string) $permit->id, $job->model_id);
        $this->assertSame('pending', $job->status);
        $this->assertSame(0, DB::transactionLevel());
        Queue::assertPushedOn('parallel', SendToRestErpJob::class);
        Queue::assertPushed(SendToRestErpJob::class, 1);
        $this->soap->shouldNotHaveReceived('run');
        Http::assertNothingSent();
        $this->assertDatabaseCount('erp_jobs', 0);
    }

    public function test_controller_preserves_broker_failure_status_and_saved_job(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Redis connection refused. password=broker-secret'));
        try {
            $this->store();
            $this->fail('Expected broker failure.');
        } catch (RestErpQueueException $exception) {
            $this->assertSame(503, $exception->getResponse()->getStatusCode());
            $this->assertDatabaseCount('gate_permits', 1);
            $job = RestErpJob::query()->where('endpoint', 'ExitRequests')->sole();
            $this->assertSame('failed', $job->status);
            $this->assertDatabaseCount('rest_erp_jobs', 2);
            $this->assertSame($job->id, $exception->getResponse()->getData(true)['rest_erp_job_id']);
            $this->assertSame(0, DB::transactionLevel());
        }
        Http::assertNothingSent();
    }

    public function test_invalid_destination_rolls_back_the_permit_and_never_dispatches(): void
    {
        DB::table('locations')->where('id', 2)->update(['code' => '']);
        try {
            $this->store();
            $this->fail('Expected rejected payload.');
        } catch (HttpResponseException $exception) {
            $this->assertSame(422, $exception->getResponse()->getStatusCode());
            $this->assertSame('VALIDATION_ERROR', $exception->getResponse()->getData(true)['error']['code']);
        }
        $this->assertDatabaseCount('gate_permits', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        $this->assertSame(0, DB::transactionLevel());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public static function autoPermitInputs(): array
    {
        $cases = [];
        foreach (['exit', 'enter'] as $type) {
            foreach ([false, true] as $batch) {
                $cases[$type.' scalar batch='.(int) $batch] = [$type, self::VIN, $batch, false];
                $cases[$type.' array batch='.(int) $batch] = [$type, [self::VIN, 'N5RT92CF6SP000482'], $batch, $batch];
            }
        }

        $cases['exit batch zero'] = ['exit', [self::VIN, 'N5RT92CF6SP000482'], true, true, '0'];
        $cases['enter batch zero'] = ['enter', [self::VIN, 'N5RT92CF6SP000482'], true, true, '0'];

        return $cases;
    }

    #[DataProvider('autoPermitInputs')]
    public function test_service_accepts_single_and_array_inputs(string $type, string|array $items, bool $batch, bool $grouped, string $itemType = '2'): void
    {
        if ($type === 'enter') {
            DB::table('location_attributes')->delete();
            DB::table('location_attributes')->insert(['location_id' => 2, 'attribute' => 'autoEnterPermit', 'value' => 'true']);
        }
        $bill = $this->persistedBill();
        $bill->load('actionLogs');
        $vins = is_array($items) ? $items : [$items];

        resolve(CreateAutoGatePermitService::class)->create($bill, $items, $itemType, $batch);

        $endpoint = $type === 'exit'
            ? ($grouped ? 'GroupExits' : 'ExitRequests')
            : ($grouped ? 'GroupReturns' : 'ReturnRequests');
        $jobs = RestErpJob::query()->where('endpoint', $endpoint)->orderBy('id')->get();
        $this->assertCount($grouped ? 1 : count($vins), $jobs);
        foreach ($jobs as $job) {
            $this->assertSame($itemType, $job->parameters[$type === 'enter' && $grouped ? 'ItemTypeP' : 'ItemType']);
        }
        $this->assertDatabaseCount('gate_permits', count($vins));
        $this->assertSame($jobs->modelKeys(), $bill->actionLogs->pluck('data.rest_erp_job_id')->all());
        if ($grouped) {
            $this->assertSame(implode(',', $vins), $jobs->sole()->parameters['VINArray']);
            $this->assertSame((string) count($vins), $jobs->sole()->parameters['NumberVIN']);
        } else {
            $this->assertSame($vins, $jobs->pluck('parameters.VIN')->all());
        }
        $prints = RestErpJob::query()->where('endpoint', 'ExitPrints')->get();
        $this->assertCount($type === 'exit' && $grouped ? count($vins) : 0, $prints);
        foreach ($prints as $print) {
            $this->assertSame($jobs->sole()->id, $print->depends_on_id);
        }
        $this->assertDatabaseCount('rest_erp_jobs', $jobs->count() + $prints->count());
        Queue::assertPushed(SendToRestErpJob::class, $jobs->count());
        Http::assertNothingSent();
    }

    public function test_batch_service_skips_existing_permits_and_duplicate_vins(): void
    {
        $service = resolve(CreateAutoGatePermitService::class);
        $bill = $this->persistedBill();
        $service->create($bill, self::VIN, '2');
        $items = ['existing' => self::VIN, 'new' => 'N5RT92CF6SP000482', 'duplicate' => 'N5RT92CF6SP000482'];

        $service->create($bill, $items, '2', true);

        $group = RestErpJob::query()->where('endpoint', 'GroupExits')->sole();
        $this->assertSame('N5RT92CF6SP000482', $group->parameters['VINArray']);
        $this->assertSame('1', $group->parameters['NumberVIN']);
        $print = RestErpJob::query()->where('endpoint', 'ExitPrints')->sole();
        $this->assertSame('N5RT92CF6SP000482', $print->parameters['VIN']);
        $this->assertSame($group->id, $print->depends_on_id);
        $this->assertSame($print->model_id, $group->model_id);
        $this->assertDatabaseCount('gate_permits', 2);
        $this->assertDatabaseCount('rest_erp_jobs', 3);
        $this->assertCount(2, $bill->actionLogs);

        $service->create($bill, $items, '2', true);

        $this->assertDatabaseCount('rest_erp_jobs', 3);
        $this->assertCount(2, $bill->fresh()->actionLogs);
        Queue::assertPushed(SendToRestErpJob::class, 2);
        Http::assertNothingSent();
    }

    public function test_service_does_nothing_without_auto_permit_configuration_or_with_empty_items(): void
    {
        $service = resolve(CreateAutoGatePermitService::class);
        $service->create($this->bill(), [], '2', true);
        DB::table('location_attributes')->delete();
        $service->create($this->bill(), self::VIN, '2');

        $this->assertDatabaseCount('gate_permits', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_exit_configuration_takes_priority_over_enter_configuration(): void
    {
        DB::table('location_attributes')->insert(['location_id' => 2, 'attribute' => 'autoEnterPermit', 'value' => 'true']);

        resolve(CreateAutoGatePermitService::class)->create($this->bill(), self::VIN, '2');

        $this->assertSame('exit', GatePermit::query()->sole()->type);
        $this->assertSame('ExitRequests', RestErpJob::query()->sole()->endpoint);
        Http::assertNothingSent();
    }

    public function test_single_auto_permits_queue_once_per_new_vin_and_skip_existing_permits(): void
    {
        $this->store();
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '0', false);
        $this->assertDatabaseCount('gate_permits', 2);
        $this->assertDatabaseCount('rest_erp_jobs', 3);
        $job = RestErpJob::query()->latest('id')->first();
        $this->assertSame(['VIN' => 'N5RT92CF6SP000482', 'TypeOfExit' => '2', 'Destination' => 'CT004156', 'ItemType' => '0'], $job->parameters);
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '0', false);
        $this->assertDatabaseCount('rest_erp_jobs', 3);
        Queue::assertPushed(SendToRestErpJob::class, 2);
        $this->soap->shouldNotHaveReceived('run');
        Http::assertNothingSent();
    }

    public function test_auto_permit_uses_bill_actor_when_called_without_authenticated_user(): void
    {
        Auth::forgetGuards();
        $this->bill()->createAutoGatePermit([self::VIN], 2, false);
        $job = RestErpJob::query()->sole();
        $this->assertSame(42, $job->user_id);
        $this->assertEquals(42, GatePermit::query()->sole()->owner_id);
        Queue::assertPushed(SendToRestErpJob::class, 1);
        Http::assertNothingSent();
    }

    public function test_invalid_auto_permit_rolls_back_and_never_queues(): void
    {
        try {
            $this->bill()->createAutoGatePermit([self::VIN], null, false);
            $this->fail('Expected validation failure.');
        } catch (\Exception $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertDatabaseCount('gate_permits', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_batch_exit_queues_group_then_prints_per_vin_without_soap(): void
    {
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '2', true);
        $this->assertDatabaseCount('gate_permits', 2);
        $this->assertDatabaseCount('rest_erp_jobs', 3);
        $group = RestErpJob::query()->where('endpoint', 'GroupExits')->sole();
        $this->assertSame(['VINArray' => self::VIN.',N5RT92CF6SP000482', 'TypeOfExit' => '2', 'Destination' => 'CT004156', 'NumberVIN' => '2', 'ItemType' => '2'], $group->parameters);
        $prints = RestErpJob::query()->where('endpoint', 'ExitPrints')->orderBy('id')->get();
        foreach ($prints as $print) {
            $this->assertSame($group->id, $print->depends_on_id);
            $this->assertSame(GatePermit::class, $print->model_type);
            $this->assertNull($print->dispatched_at);
        }
        Queue::assertPushed(SendToRestErpJob::class, 1);
        Http::assertNothingSent();
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(409, $response->getStatusCode());
        Http::fake(['*' => Http::response([], 200)]);
        resolve(RestErpJobService::class)->execute($group->id);
        Queue::assertPushed(SendToRestErpJob::class, 3);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(409, $response->getStatusCode());
        foreach ($prints as $print) {
            resolve(RestErpJobService::class)->execute($print->id);
        }
        $this->assertSame(['GroupExits', 'ExitPrints', 'ExitPrints'], Http::recorded()->map(fn ($pair) => basename($pair[0]->url()))->all());
        $this->assertSame(['VIN' => self::VIN, 'ItemType' => '2'], Http::recorded()[1][0]->data());
        $this->assertSame(['VIN' => 'N5RT92CF6SP000482', 'ItemType' => '2'], Http::recorded()[2][0]->data());
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseCount('production_tracking', 1);
        $this->assertSoftDeleted(GatePermit::withTrashed()->where('model_id', self::VIN)->sole());
        $this->soap->shouldNotHaveReceived('run');
    }

    public function test_group_and_print_failures_block_transfer_and_print_retry_does_not_recreate_group(): void
    {
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '2', true);
        $group = RestErpJob::query()->where('endpoint', 'GroupExits')->sole();
        $print = RestErpJob::query()->where('endpoint', 'ExitPrints')->where('parameters->VIN', self::VIN)->sole();
        $service = resolve(RestErpJobService::class);
        Http::fakeSequence()
            ->push(['error' => ['code' => 'Application_DialogException', 'message' => 'Group refused.']], 400)
            ->push([], 200)
            ->push(['error' => ['code' => 'Application_DialogException', 'message' => 'Print refused.']], 400)
            ->push([], 200)
            ->push([], 200);
        $service->execute($group->id);
        $this->assertNull($print->fresh()->dispatched_at);
        $this->assertSame(409, resolve(GatePermitTransferController::class)($this->vehicle())->getStatusCode());
        $service->retry($group->fresh(), 42);
        $service->execute($group->id);
        $service->execute($print->id);
        $this->assertSame('failed', $print->fresh()->status);
        $this->assertSame(409, resolve(GatePermitTransferController::class)($this->vehicle())->getStatusCode());
        $this->assertDatabaseCount('production_tracking', 0);
        $this->assertDatabaseCount('gate_permits', 2);
        $service->retry($print->fresh(), 42);
        $service->execute($print->id);
        $this->assertSame(200, resolve(GatePermitTransferController::class)($this->vehicle())->getStatusCode());
        $this->assertSame(2, $group->fresh()->tries);
        $this->assertSame(['GroupExits', 'GroupExits', 'ExitPrints', 'ExitPrints', 'ApproveExitZones'], Http::recorded()->map(fn ($pair) => basename($pair[0]->url()))->all());
        $this->soap->shouldNotHaveReceived('run');
    }

    public function test_invalid_group_rolls_back_permits_and_dependent_print_jobs(): void
    {
        try {
            $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], null, true);
            $this->fail('Expected validation failure.');
        } catch (\Exception $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertDatabaseCount('gate_permits', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_batch_enter_queues_group_return_and_links_it_to_the_bill_without_soap(): void
    {
        DB::table('location_attributes')->delete();
        DB::table('location_attributes')->insert(['location_id' => 2, 'attribute' => 'autoEnterPermit', 'value' => 'true']);
        $bill = $this->persistedBill();
        $bill->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '2', true);

        $this->assertDatabaseCount('gate_permits', 2);
        $job = RestErpJob::query()->sole();
        $this->assertSame('GroupReturns', $job->endpoint);
        $this->assertSame('pending', $job->status);
        $this->assertSame(42, $job->user_id);
        $this->assertSame([
            'VINArray' => self::VIN.',N5RT92CF6SP000482',
            'Destination' => 'CT004156', 'NumberVIN' => '2', 'ItemTypeP' => '2',
        ], $job->parameters);
        $this->assertSame(GatePermit::class, $job->model_type);
        $this->assertSame('enter', GatePermit::query()->findOrFail($job->model_id)->type);
        $this->assertSame($job->id, $bill->actionLogs()->where('action', 'rest_erp_return_requested')->sole()->data['rest_erp_job_id']);
        $this->assertSame([$job->id], resolve(RestErpJobLinksService::class)->forBillOfLading($bill)->modelKeys());
        Queue::assertPushed(SendToRestErpJob::class, 1);
        Http::assertNothingSent();
        $this->soap->shouldNotHaveReceived('run');
    }

    public function test_invalid_batch_return_rolls_back_permits_and_job(): void
    {
        DB::table('location_attributes')->delete();
        DB::table('location_attributes')->insert(['location_id' => 2, 'attribute' => 'autoEnterPermit', 'value' => 'true']);

        try {
            $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], null, true);
            $this->fail('Expected validation failure.');
        } catch (\Exception $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertDatabaseCount('gate_permits', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
        $this->soap->shouldNotHaveReceived('run');
    }

    public function test_manual_enter_queues_return_request_without_soap(): void
    {
        $response = $this->store(['type' => 'enter', 'exit_type' => null]);
        $this->assertSame(202, $response->getStatusCode());
        $permit = GatePermit::query()->sole();
        $job = RestErpJob::query()->sole();
        $this->assertSame('ReturnRequests', $job->endpoint);
        $this->assertSame('pending', $job->status);
        $this->assertSame((string) $permit->id, $job->model_id);
        $this->assertSame(GatePermit::class, $job->model_type);
        $this->assertSame(42, $job->user_id);
        $this->assertSame(['VIN' => self::VIN, 'Destination' => 'CT004156', 'ItemType' => '2'], $job->parameters);
        $this->assertSame($job->id, $response->getData(true)['rest_erp_job_id']);
        $this->assertSame($job->uuid, $response->getData(true)['request_id']);
        $this->assertSame($permit->id, $response->getData(true)['data']['id']);
        $this->assertSame(0, DB::transactionLevel());
        Queue::assertPushed(SendToRestErpJob::class, 1);
        Http::assertNothingSent();
        $this->soap->shouldNotHaveReceived('run');
    }

    public function test_auto_enter_queues_one_return_per_vin_and_links_jobs_to_bill(): void
    {
        DB::table('location_attributes')->delete();
        DB::table('location_attributes')->insert(['location_id' => 2, 'attribute' => 'autoEnterPermit', 'value' => 'true']);
        Auth::logout();
        $bill = $this->persistedBill();
        $vins = [self::VIN, 'N5RT92CF6SP000482'];
        $bill->createAutoGatePermit($vins, '0', false);

        $this->assertDatabaseCount('gate_permits', 2);
        $jobs = RestErpJob::query()->orderBy('id')->get();
        $this->assertCount(2, $jobs);
        foreach ($jobs as $index => $job) {
            $this->assertSame('ReturnRequests', $job->endpoint);
            $this->assertSame('pending', $job->status);
            $this->assertSame(42, $job->user_id);
            $this->assertSame(['VIN' => $vins[$index], 'Destination' => 'CT004156', 'ItemType' => '0'], $job->parameters);
            $this->assertSame($vins[$index], GatePermit::query()->findOrFail($job->model_id)->model_id);
        }
        $this->assertSame($jobs->modelKeys(), $bill->actionLogs()->where('action', 'rest_erp_return_requested')->orderBy('id')->get()->pluck('data.rest_erp_job_id')->all());
        $this->assertSame(array_reverse($jobs->modelKeys()), resolve(RestErpJobLinksService::class)->forBillOfLading($bill)->modelKeys());
        $bill->createAutoGatePermit($vins, '0', false);
        $this->assertDatabaseCount('rest_erp_jobs', 2);
        Queue::assertPushed(SendToRestErpJob::class, 2);
        Http::assertNothingSent();
        $this->soap->shouldNotHaveReceived('run');
    }

    public function test_invalid_auto_return_rolls_back_permits_and_jobs(): void
    {
        DB::table('location_attributes')->delete();
        DB::table('location_attributes')->insert(['location_id' => 2, 'attribute' => 'autoEnterPermit', 'value' => 'true']);
        try {
            $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], null, false);
            $this->fail('Expected validation failure.');
        } catch (\Exception $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertDatabaseCount('gate_permits', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
        $this->soap->shouldNotHaveReceived('run');
    }

    public function test_invalid_manual_return_rolls_back_permit_and_job(): void
    {
        DB::table('locations')->where('id', 2)->update(['code' => '']);
        try {
            $this->store(['type' => 'enter', 'exit_type' => null]);
            $this->fail('Expected validation failure.');
        } catch (HttpResponseException $exception) {
            $this->assertSame(422, $exception->getResponse()->getStatusCode());
        }
        $this->assertDatabaseCount('gate_permits', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
        $this->soap->shouldNotHaveReceived('run');
    }

    public function test_gate_resource_links_the_group_parent_and_its_own_print_and_can_retry_the_print(): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '2', true);
        $permit = GatePermit::query()->where('model_id', self::VIN)->sole();
        $group = RestErpJob::query()->where('endpoint', 'GroupExits')->sole();
        $print = RestErpJob::query()->where('endpoint', 'ExitPrints')->where('model_id', $permit->id)->sole();
        $group->update(['status' => 'success']);
        $print->update(['status' => 'failed', 'error_code' => 'ERP_ERROR']);
        $retryUrl = $this->permitRetryUrl($permit);
        $this->assertSame(route('rest-erp-jobs.retry', ['restErpJob' => $print->id]), $retryUrl);
        $this->putJson($retryUrl)->assertStatus(202)->assertJsonPath('data.status', 'pending');
        $this->assertSame(0, $print->fresh()->actionLogs()->where('action', 'rest_erp_retry_requested')->count());
        Http::assertNothingSent();
    }

    public function test_gate_resource_selects_the_latest_retryable_related_job_and_can_fall_back_to_its_parent(): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '2', true);
        $permit = GatePermit::query()->where('model_id', self::VIN)->sole();
        $group = RestErpJob::query()->where('endpoint', 'GroupExits')->sole();
        $print = RestErpJob::query()->where('endpoint', 'ExitPrints')->where('model_id', $permit->id)->sole();
        $group->update(['status' => 'uncertain']);
        RestErpJob::query()->where('endpoint', 'ExitPrints')->update(['status' => 'failed']);

        $this->assertSame(route('rest-erp-jobs.retry', ['restErpJob' => $print->id]), $this->permitRetryUrl($permit));
        $print->refresh()->update(['status' => 'pending']);
        $this->assertSame(route('rest-erp-jobs.retry', ['restErpJob' => $group->id]), $this->permitRetryUrl($permit));
        $group->update(['status' => 'success']);
        $this->assertNull($this->permitRetryUrl($permit));
    }

    public function test_gate_resource_does_not_expose_retry_url_without_permission_or_for_an_anonymous_user(): void
    {
        $this->store();
        RestErpJob::query()->update(['status' => 'failed']);
        $permit = GatePermit::query()->sole();
        $this->assertNull($this->permitRetryUrl($permit));
        $request = $this->retryRequest();
        $request->setUserResolver(fn () => null);
        $resource = (new GatePermitResource($permit))->resolve($request);
        $this->assertNull($resource['retry_url']);
        $this->assertArrayNotHasKey('rest_erp_jobs', $resource);
    }

    public function test_transfer_waits_for_a_reexecuted_group_even_when_its_existing_print_succeeded(): void
    {
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '2', true);
        Http::fake(['*' => Http::response([], 200)]);
        foreach (RestErpJob::query()->oldest('id')->get() as $job) {
            resolve(RestErpJobService::class)->execute($job->id);
        }
        $group = RestErpJob::query()->where('endpoint', 'GroupExits')->sole();
        $owner = GatePermit::query()->findOrFail($group->model_id);
        $payload = $group->parameters;
        $queued = resolve(GroupExitsService::class)->queue(
            $payload['VINArray'], $payload['TypeOfExit'], $payload['Destination'],
            $payload['NumberVIN'], $payload['ItemType'], model: $owner
        );
        $this->assertSame($group->id, $queued['data']['rest_erp_job_id']);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame($group->id, $response->getData(true)['rest_erp_job_id']);
        $this->assertDatabaseCount('rest_erp_jobs', 3);
        $this->assertDatabaseCount('production_tracking', 0);
        Http::assertSentCount(3);
    }

    public function test_gate_resource_does_not_retry_an_old_duplicate_when_the_current_operation_succeeded(): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        $this->store();
        $job = RestErpJob::query()->where('endpoint', 'ExitRequests')->sole();
        $job->update(['status' => 'failed']);
        // Keep historical duplicates, as requested, while treating the newest record as canonical.
        $current = $job->replicate(['uuid']);
        $current->fill(['uuid' => (string) Str::uuid(), 'status' => 'success'])->save();
        $this->assertNull($this->permitRetryUrl(GatePermit::query()->sole()));
        $this->assertDatabaseCount('rest_erp_jobs', 3);
    }

    public function test_bill_resource_keeps_exact_request_ids_and_prints_without_vin_based_leakage(): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        $bill = $this->persistedBill();
        $bill->vehicles()->syncWithPivotValues([self::VIN, 'N5RT92CF6SP000482'], ['type' => 'bodies']);
        $bill->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '2', true);
        $group = RestErpJob::query()->where('endpoint', 'GroupExits')->sole();
        $group->update(['status' => 'uncertain']);
        $other = resolve(ExitRequestsService::class)->queue(self::VIN, 1, 'CT004156', 2);
        RestErpJob::query()->findOrFail($other['data']['rest_erp_job_id'])->update(['status' => 'failed']);
        $request = $this->retryRequest();
        $data = (new BillOfLadingResource($bill))->resolve($request);
        $jobs = $data['rest_erp_jobs']->resolve($request);
        $this->assertCount(3, $jobs);
        $this->assertNotContains($other['data']['rest_erp_job_id'], array_column($jobs, 'id'));
        $groupData = array_values(array_filter($jobs, fn ($job) => $job['id'] === $group->id))[0];
        $this->assertSame(route('rest-erp-jobs.retry', ['restErpJob' => $group->id]), $groupData['retry_url']);
        $this->assertTrue($groupData['retry_requires_acknowledgement']);
        foreach ($jobs as $job) {
            if ($job['endpoint'] === 'ExitPrints') {
                $this->assertSame($group->id, $job['depends_on_id']);
                $this->assertNull($job['retry_url']);
            }
        }
        $this->assertSame($group->id, $bill->actionLogs()->where('action', 'rest_erp_exit_requested')->sole()->data['rest_erp_job_id']);
        $this->putJson($groupData['retry_url'])->assertUnprocessable();
        $this->putJson($groupData['retry_url'], ['acknowledge_uncertain_result' => true])->assertStatus(202);
        Http::assertNothingSent();
    }

    public function test_bill_resource_links_failed_approval_for_its_exact_permit(): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        $bill = $this->persistedBill();
        $bill->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '2', true);
        RestErpJob::query()->update(['status' => 'success']);
        Http::fake(['*' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Approval refused.']], 400)]);
        resolve(GatePermitTransferController::class)($this->vehicle());
        $approval = RestErpJob::query()->where('endpoint', 'ApproveExitZones')->sole();
        $request = $this->retryRequest();
        $data = (new BillOfLadingResource($bill))->resolve($request);
        $jobs = $data['rest_erp_jobs']->resolve($request);
        $this->assertCount(4, $jobs);
        $this->assertSame($approval->id, $jobs[0]['id']);
        $this->assertSame(route('rest-erp-jobs.retry', ['restErpJob' => $approval->id]), $jobs[0]['retry_url']);
        $this->assertSame('PUT', $jobs[0]['retry_method']);
    }

    public function test_single_auto_request_is_linked_to_the_bill_and_persists_after_soft_delete(): void
    {
        $bill = $this->persistedBill();
        $bill->createAutoGatePermit([self::VIN], '2', false);
        $job = RestErpJob::query()->sole();
        GatePermit::query()->sole()->delete();
        $request = $this->retryRequest();
        $data = (new BillOfLadingResource($bill))->resolve($request);
        $jobs = $data['rest_erp_jobs']->resolve($request);
        $this->assertSame([$job->id], array_column($jobs, 'id'));
        $this->assertNull($jobs[0]['retry_url']);
    }

    public static function unapprovedStatuses(): array
    {
        return array_map(fn ($status) => [$status], ['pending', 'in-progress', 'failed', 'uncertain', 'rejected', 'ignored']);
    }

    #[DataProvider('unapprovedStatuses')]
    public function test_transfer_waits_for_successful_exit_request(string $status): void
    {
        $this->store();
        $job = RestErpJob::query()->where('endpoint', 'ExitRequests')->sole();
        $job->update(['status' => $status]);
        $permit = GatePermit::query()->sole();
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('ERP_EXIT_REQUEST_NOT_READY', $response->getData(true)['error']['code']);
        $print = RestErpJob::query()->where('endpoint', 'ExitPrints')->sole();
        $this->assertSame($print->id, $response->getData(true)['rest_erp_job_id']);
        $this->assertSame($job->id, $print->depends_on_id);
        $this->assertNull($print->dispatched_at);
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 2);
        Http::assertNothingSent();
    }

    public function test_worker_success_allows_transfer_but_worker_failure_does_not(): void
    {
        $this->store();
        $job = RestErpJob::query()->where('endpoint', 'ExitRequests')->sole();
        Http::fake(['*/ExitRequests' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Request refused.']], 400)]);
        resolve(RestErpJobService::class)->execute($job->id);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(409, $response->getStatusCode());
        $this->assertNotSoftDeleted(GatePermit::query()->sole());
        $this->assertDatabaseCount('production_tracking', 0);
        $print = RestErpJob::query()->where('endpoint', 'ExitPrints')->sole();
        $this->assertNull($print->dispatched_at);
        resolve(RestErpJobService::class)->execute($print->id);
        Http::assertSentCount(1);
        // The shared retry mechanism uses the same audited request.
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['*/ExitRequests' => Http::response([], 200), '*/ExitPrints' => Http::response([], 200), '*/ApproveExitZones' => Http::response([], 200)]);
        resolve(RestErpJobService::class)->retry($job->fresh(), 42);
        resolve(RestErpJobService::class)->execute($job->id);
        $this->assertNotNull($print->fresh()->dispatched_at);
        Queue::assertPushed(SendToRestErpJob::class, fn ($queued) => $queued->id === (string) $print->id);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(409, $response->getStatusCode());
        resolve(RestErpJobService::class)->execute($print->id);
        $this->assertSame('success', $print->fresh()->status);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseCount('production_tracking', 1);
        $this->assertSoftDeleted(GatePermit::withTrashed()->sole());
        Http::assertSentCount(3);
        $this->soap->shouldNotHaveReceived('run');
    }
}
