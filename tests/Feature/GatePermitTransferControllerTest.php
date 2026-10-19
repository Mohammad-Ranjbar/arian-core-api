<?php

namespace Tests\Feature;

use App\Events\ErpJobEvent;
use App\Http\Controllers\GatePermitTransferController;
use App\Models\GatePermit;
use App\Models\Location;
use App\Models\RestErpJob;
use App\Models\Tracking;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;
use TypeError;

class GatePermitTransferControllerTest extends TestCase
{
    use CreatesRestErpTables;

    private const VIN = 'N5RSU7SN5R2902014';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Bus::fake();
        Queue::fake();
        Event::fake([ErpJobEvent::class]);
        Gate::define('gate-permit-exit', fn ($user) => $user->id === 42);

        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('gate_permits', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('model_id');
            $table->string('type');
            $table->string('item_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        (require database_path('migrations/2023_11_01_134413_create_production_trackings_table.php'))->up();
        (require database_path('migrations/2026_09_13_100000_add_soft_deletes_to_production_tracking_table.php'))->up();
        DB::table('vehicles')->insert(['vin' => self::VIN]);
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::without(['carModel', 'makeCode'])->findOrFail(self::VIN);
    }

    private function permit(string $type = 'exit', ?string $itemType = '2'): GatePermit
    {
        return GatePermit::query()->create([
            'model_type' => Vehicle::class, 'model_id' => self::VIN,
            'type' => $type, 'item_type' => $itemType, 'source_id' => 7,
        ]);
    }

    public static function directions(): array
    {
        return [
            'exit' => ['exit', 'ApproveExitZones', 'BarcodeInfo'],
            'enter' => ['enter', 'ApproveReturnZones', 'VIN'],
        ];
    }

    #[DataProvider('directions')]
    public function test_it_approves_the_latest_permit_before_registering_tracking(string $type, string $endpoint, string $field): void
    {
        $older = $this->permit($type === 'exit' ? 'enter' : 'exit', '1');
        $older->forceFill(['created_at' => now()->subDay()])->save();
        $permit = $this->permit($type);
        $otherVin = $this->permit($type);
        $otherVin->forceFill(['model_id' => 'OTHER', 'created_at' => now()->addDay()])->save();
        $otherModel = $this->permit($type);
        $otherModel->forceFill(['model_type' => 'OtherModel', 'created_at' => now()->addDays(2)])->save();
        Http::fake(function (Request $request) use ($permit) {
            $this->assertDatabaseCount('production_tracking', 0);
            $this->assertNotSoftDeleted($permit);

            return Http::response(['approved' => true], 200);
        });

        $response = resolve(GatePermitTransferController::class)($this->vehicle());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'تردد ثبت گردید'], $response->getData(true));
        Http::assertSentCount(1);
        $request = Http::recorded()->sole()[0];
        $this->assertSame(config('rest_erp.base_url').'/'.$endpoint, $request->url());
        $this->assertSame([$field => self::VIN, 'ItemType' => '2'], $request->data());
        $this->assertSoftDeleted($permit);
        foreach ([$older, $otherVin, $otherModel] as $remaining) {
            $this->assertNotSoftDeleted($remaining);
        }
        $tracking = Tracking::query()->sole();
        $this->assertSame(self::VIN, $tracking->vin);
        $this->assertSame(Location::class, $tracking->model_type);
        $this->assertEquals($permit->source_id, $tracking->model_id);
        $this->assertSame($type, $tracking->type);
        $this->assertEquals(42, $tracking->user_id);
        $job = RestErpJob::query()->sole();
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame('success', $job->status);
        $this->assertSame(GatePermit::class, $job->model_type);
        $this->assertSame((string) $permit->id, $job->model_id);
        $this->assertSame($endpoint, $job->endpoint);
        $this->assertCount(2, $job->actionLogs()->get());
        $this->assertNoQueuedOrLegacyDispatch();
    }

    public static function failedApprovals(): array
    {
        $cases = [];
        foreach (self::directions() as $name => [$type]) {
            $cases[$name.' business'] = [$type, 400, 422, 'Application_DialogException'];
            $cases[$name.' auth'] = [$type, 401, 502, 'ERP_AUTH_ERROR'];
            $cases[$name.' server'] = [$type, 500, 502, 'ERP_ERROR'];
            $cases[$name.' connection'] = [$type, 0, 503, 'ERP_CONNECTION_ERROR'];
        }

        return $cases;
    }

    #[DataProvider('failedApprovals')]
    public function test_erp_failure_preserves_permit_and_audit_without_tracking(string $type, int $providerStatus, int $httpStatus, string $code): void
    {
        $permit = $this->permit($type);
        Http::fake(function () use ($providerStatus) {
            if ($providerStatus === 0) {
                throw new ConnectionException('Timeout.');
            }

            return Http::response($providerStatus === 400 ? ['error' => ['code' => 'Application_DialogException', 'message' => 'Approval refused.']] : [], $providerStatus);
        });

        $response = resolve(GatePermitTransferController::class)($this->vehicle());

        $this->assertSame($httpStatus, $response->getStatusCode());
        $this->assertSame($code, $response->getData(true)['error']['code']);
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        $job = RestErpJob::query()->sole();
        $this->assertSame($code, $job->error_code);
        $this->assertSame($httpStatus, $job->http_status);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame(['rest_erp_created', 'rest_erp_'.$job->status], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        Http::assertSentCount($providerStatus === 0 ? 0 : 1);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertNoQueuedOrLegacyDispatch();
    }

    #[DataProvider('directions')]
    public function test_missing_item_type_is_rejected_without_consuming_permit(string $type): void
    {
        $permit = $this->permit($type, null);
        Http::fake();
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('VALIDATION_ERROR', $response->getData(true)['error']['code']);
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        $this->assertSame('rejected', RestErpJob::query()->sole()->status);
        Http::assertNothingSent();
        $this->assertNoQueuedOrLegacyDispatch();
    }

    public static function localFailures(): array
    {
        return ['tracking insert' => ['tracking'], 'tracking observer type error' => ['type-error'], 'permit deletion' => ['delete']];
    }

    #[DataProvider('localFailures')]
    public function test_local_write_failure_rolls_back_tracking_and_preserves_the_erp_result(string $failure): void
    {
        $permit = $this->permit();
        if (in_array($failure, ['tracking', 'type-error'], true)) {
            Tracking::created(function () use ($failure) {
                if ($failure === 'type-error') {
                    throw new TypeError('Tracking observer failed.');
                }
                throw new RuntimeException('Tracking write failed.');
            });
        } else {
            DB::unprepared("CREATE TRIGGER reject_permit_delete BEFORE UPDATE OF deleted_at ON gate_permits BEGIN SELECT RAISE(FAIL, 'Permit deletion failed.'); END");
        }
        Http::fake(['*' => Http::response(['approved' => true], 200)]);

        $response = resolve(GatePermitTransferController::class)($this->vehicle());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        $job = RestErpJob::query()->sole();
        $this->assertSame('success', $job->status);
        $this->assertSame(['rest_erp_created', 'rest_erp_success'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        $this->assertSame(0, DB::transactionLevel());
        $this->assertNoQueuedOrLegacyDispatch();
    }

    public function test_unknown_direction_does_not_send_or_consume_the_permit(): void
    {
        $permit = $this->permit('unknown');
        Http::fake();
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Http::assertNothingSent();
        $this->assertNoQueuedOrLegacyDispatch();
    }

    public function test_missing_permit_does_not_send_or_register_tracking(): void
    {
        Http::fake();
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(400, $response->getStatusCode());
        $this->assertDatabaseCount('production_tracking', 0);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Http::assertNothingSent();
        $this->assertNoQueuedOrLegacyDispatch();
    }

    public function test_gate_permit_exit_permission_is_required(): void
    {
        $permit = $this->permit();
        Gate::define('gate-permit-exit', fn () => false);
        Http::fake();
        try {
            resolve(GatePermitTransferController::class)($this->vehicle());
            $this->fail('Authorization must fail.');
        } catch (AuthorizationException) {
            $this->assertNotSoftDeleted($permit);
            $this->assertDatabaseCount('production_tracking', 0);
            $this->assertDatabaseCount('rest_erp_jobs', 0);
            Http::assertNothingSent();
            $this->assertNoQueuedOrLegacyDispatch();
        }
    }

    #[DataProvider('directions')]
    public function test_approval_retry_remains_sync_and_transfer_reuses_its_success(string $type): void
    {
        $permit = $this->permit($type);
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        Http::fakeSequence()
            ->push(['error' => ['code' => 'Application_DialogException', 'message' => 'Approval refused.']], 400)
            ->push([], 200);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(422, $response->getStatusCode());
        $job = RestErpJob::query()->sole();
        $this->putJson(route('rest-erp-jobs.retry', ['restErpJob' => $job->id]))
            ->assertOk()->assertJsonPath('data.status', 'success')->assertJsonPath('data.execution_mode', 'sync')
            ->assertJsonPath('data.queue', null)->assertJsonPath('data.retry_url', null);
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        $response = resolve(GatePermitTransferController::class)($this->vehicle());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 1);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        $this->assertSame(2, $job->fresh()->tries);
        Http::assertSentCount(2);
        $this->assertNoQueuedOrLegacyDispatch();
    }

    #[DataProvider('directions')]
    public function test_repeating_transfer_reexecutes_the_same_failed_sync_approval(string $type): void
    {
        $permit = $this->permit($type);
        Http::fakeSequence()
            ->push(['error' => ['code' => 'Application_DialogException', 'message' => 'Approval refused.']], 400)
            ->push([], 200);
        $this->assertSame(422, resolve(GatePermitTransferController::class)($this->vehicle())->getStatusCode());
        $original = RestErpJob::query()->sole();
        $this->assertSame(200, resolve(GatePermitTransferController::class)($this->vehicle())->getStatusCode());
        $job = RestErpJob::query()->sole();
        $this->assertSame($original->id, $job->id);
        $this->assertSame($original->uuid, $job->uuid);
        $this->assertSame(2, $job->tries);
        $this->assertSame('success', $job->status);
        $this->assertSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 1);
        $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2066}Approval refused. \u{2069}\u{2069}", $job->actionLogs()->where('action', 'rest_erp_failed')->sole()->data['snapshot']['description']);
        Http::assertSentCount(2);
        $this->assertNoQueuedOrLegacyDispatch();
    }

    #[DataProvider('directions')]
    public function test_failed_sync_retry_returns_the_erp_http_status(string $type): void
    {
        $permit = $this->permit($type);
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        Http::fake(['*' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Approval refused.']], 400)]);
        resolve(GatePermitTransferController::class)($this->vehicle());
        $job = RestErpJob::query()->sole();
        $this->putJson(route('rest-erp-jobs.retry', ['restErpJob' => $job->id]))
            ->assertStatus(422)->assertJsonPath('data.status', 'failed')->assertJsonPath('data.execution_mode', 'sync');
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        Http::assertSentCount(2);
        $this->assertNoQueuedOrLegacyDispatch();
    }

    public function test_an_uncertain_sync_retry_requires_acknowledgement(): void
    {
        $this->permit();
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        Http::fakeSequence()->push([], 500)->push([], 200);
        resolve(GatePermitTransferController::class)($this->vehicle());
        $job = RestErpJob::query()->sole();
        $this->assertSame('uncertain', $job->status);
        $url = route('rest-erp-jobs.retry', ['restErpJob' => $job->id]);
        $this->putJson($url)->assertUnprocessable();
        Http::assertSentCount(1);
        $this->putJson($url, ['acknowledge_uncertain_result' => true])->assertOk()->assertJsonPath('data.status', 'success');
        Http::assertSentCount(2);
        $this->assertNoQueuedOrLegacyDispatch();
    }

    public function test_transfer_does_not_duplicate_an_approval_retry_in_progress(): void
    {
        $permit = $this->permit();
        Http::fake(['*' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Approval refused.']], 400)]);
        resolve(GatePermitTransferController::class)($this->vehicle());
        RestErpJob::query()->sole()->update(['status' => 'in-progress']);
        $this->assertSame(409, resolve(GatePermitTransferController::class)($this->vehicle())->getStatusCode());
        $this->assertNotSoftDeleted($permit);
        $this->assertDatabaseCount('production_tracking', 0);
        Http::assertSentCount(1);
        $this->assertNoQueuedOrLegacyDispatch();
    }

    private function assertNoQueuedOrLegacyDispatch(): void
    {
        Bus::assertNothingDispatched();
        Queue::assertNothingPushed();
        Event::assertNotDispatched(ErpJobEvent::class);
        $this->assertDatabaseCount('erp_jobs', 0);
    }
}
