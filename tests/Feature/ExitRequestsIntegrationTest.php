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
use App\Services\Erp\RestApi\ExitRequestsService;
use App\Services\Erp\RestApi\GroupExitsService;
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
        $job = RestErpJob::query()->sole();
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
            $job = RestErpJob::query()->sole();
            $this->assertSame('failed', $job->status);
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

    public function test_single_auto_permits_queue_once_per_new_vin_and_skip_existing_permits(): void
    {
        $this->store();
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '0', false);
        $this->assertDatabaseCount('gate_permits', 2);
        $this->assertDatabaseCount('rest_erp_jobs', 2);
        $job = RestErpJob::query()->latest('id')->first();
        $this->assertSame(['VIN' => 'N5RT92CF6SP000482', 'TypeOfExit' => '2', 'Destination' => 'CT004156', 'ItemType' => '0'], $job->parameters);
        $this->bill()->createAutoGatePermit([self::VIN, 'N5RT92CF6SP000482'], '0', false);
        $this->assertDatabaseCount('rest_erp_jobs', 2);
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

    public function test_manual_enter_keeps_return_request_and_does_not_queue_exit_request(): void
    {
        $this->soap->shouldReceive('run')->once()->with(config('erp.return_request'), 'ReturnRequest', [
            'vINP' => self::VIN, 'destinationP' => 'CT004156', 'itemTypeP' => '2',
        ])->andReturn(['status' => 'ok']);
        $response = $this->store(['type' => 'enter', 'exit_type' => null]);
        $this->assertInstanceOf(GatePermitResource::class, $response);
        $this->assertDatabaseCount('gate_permits', 1);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        $this->assertSame(0, DB::transactionLevel());
        Queue::assertNothingPushed();
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
        $this->assertSame(1, $print->fresh()->actionLogs()->where('action', 'rest_erp_retry_requested')->count());
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
        $job = RestErpJob::query()->sole();
        $job->update(['status' => 'failed']);
        // Keep historical duplicates, as requested, while treating the newest record as canonical.
        $current = $job->replicate(['uuid']);
        $current->fill(['uuid' => (string) Str::uuid(), 'status' => 'success'])->save();
        $this->assertNull($this->permitRetryUrl(GatePermit::query()->sole()));
        $this->assertDatabaseCount('rest_erp_jobs', 2);
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
        $job = RestErpJob::query()->sole();
        $job->update(['status' => $status]);
        $permit = GatePermit::query()->sole();
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('ERP_EXIT_REQUEST_NOT_READY', $response->getData(true)['error']['code']);
        $this->assertSame($job->id, $response->getData(true)['rest_erp_job_id']);
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        Http::assertNothingSent();
    }

    public function test_worker_success_allows_transfer_but_worker_failure_does_not(): void
    {
        $this->store();
        $job = RestErpJob::query()->sole();
        Http::fake(['*/ExitRequests' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Request refused.']], 400)]);
        resolve(RestErpJobService::class)->execute($job->id);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(409, $response->getStatusCode());
        $this->assertNotSoftDeleted(GatePermit::query()->sole());
        $this->assertDatabaseCount('production_tracking', 0);
        Http::assertSentCount(1);
        // The shared retry mechanism uses the same audited request.
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['*/ExitRequests' => Http::response([], 200), '*/ApproveExitZones' => Http::response([], 200)]);
        resolve(RestErpJobService::class)->retry($job->fresh(), 42);
        resolve(RestErpJobService::class)->execute($job->id);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseCount('production_tracking', 1);
        $this->assertSoftDeleted(GatePermit::withTrashed()->sole());
        Http::assertSentCount(2);
        $this->soap->shouldNotHaveReceived('run');
    }
}
