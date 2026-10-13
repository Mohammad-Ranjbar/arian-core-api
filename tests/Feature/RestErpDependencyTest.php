<?php

namespace Tests\Feature;

use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Models\User;
use App\Services\Erp\RestApi\ExitPrintsService;
use App\Services\Erp\RestApi\GroupExitsService;
use App\Services\Erp\RestApi\RestErpJobService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class RestErpDependencyTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
    }

    private function workflow(): array
    {
        return DB::transaction(function () {
            $group = resolve(GroupExitsService::class)->queue('VIN-1,VIN-2', 1, 'DESTINATION', 2, 2);
            $prints = [];
            foreach (['VIN-1', 'VIN-2'] as $vin) {
                $result = resolve(ExitPrintsService::class)->queue($vin, 2, dependsOnId: $group['data']['rest_erp_job_id']);
                $prints[] = RestErpJob::query()->findOrFail($result['data']['rest_erp_job_id']);
            }

            return [RestErpJob::query()->findOrFail($group['data']['rest_erp_job_id']), $prints];
        });
    }

    public function test_prints_wait_for_group_success_even_if_the_worker_is_invoked_directly(): void
    {
        [$group, $prints] = $this->workflow();
        Queue::assertPushed(SendToRestErpJob::class, 1);
        $service = resolve(RestErpJobService::class);
        foreach ($prints as $print) {
            $this->assertSame($group->id, $print->depends_on_id);
            $this->assertFalse($service->publish($print->id));
            $this->assertNull($service->execute($print->id));
            $this->assertSame(0, $print->fresh()->tries);
            $this->assertNull($print->fresh()->dispatched_at);
        }
        Http::assertNothingSent();
        Http::fake(['*' => Http::response([], 200)]);
        $service->execute($group->id);
        Queue::assertPushed(SendToRestErpJob::class, 3);
        foreach ($prints as $print) {
            $this->assertNotNull($print->fresh()->dispatched_at);
            $this->assertSame('pending', $print->fresh()->status);
            $service->execute($print->id);
            $this->assertSame('success', $print->fresh()->status);
        }
        Http::assertSentCount(3);
    }

    public function test_group_failure_blocks_prints_and_retry_releases_them_once(): void
    {
        [$group, $prints] = $this->workflow();
        Http::fakeSequence()->push(['error' => ['code' => 'Application_DialogException', 'message' => 'Request refused.']], 400)->push([], 200);
        $service = resolve(RestErpJobService::class);
        $service->execute($group->id);
        Queue::assertPushed(SendToRestErpJob::class, 1);
        foreach ($prints as $print) {
            $this->assertNull($service->execute($print->id));
            $this->assertNull($print->fresh()->dispatched_at);
        }
        $service->retry($group->fresh(), 42);
        $service->execute($group->id);
        Queue::assertPushed(SendToRestErpJob::class, 4);
        $this->assertNull($service->execute($group->id));
        Queue::assertPushed(SendToRestErpJob::class, 4);
        Http::assertSentCount(2);
    }

    public function test_failed_print_retry_does_not_repeat_group_request(): void
    {
        [$group, $prints] = $this->workflow();
        Http::fakeSequence()->push([], 200)->push(['error' => ['code' => 'Application_DialogException', 'message' => 'Print refused.']], 400)->push([], 200);
        $service = resolve(RestErpJobService::class);
        $service->execute($group->id);
        $service->execute($prints[0]->id);
        $this->assertSame('failed', $prints[0]->fresh()->status);
        $service->retry($prints[0]->fresh(), 42);
        $service->execute($prints[0]->id);
        $this->assertSame('success', $prints[0]->fresh()->status);
        $this->assertSame(1, $group->fresh()->tries);
        $this->assertSame(2, $prints[0]->fresh()->tries);
        Http::assertSentCount(3);
        $this->assertSame(['GroupExits', 'ExitPrints', 'ExitPrints'], Http::recorded()->map(fn ($pair) => basename($pair[0]->url()))->all());
    }

    public function test_dependent_broker_failure_keeps_parent_success_and_releases_other_prints(): void
    {
        [$group, $prints] = $this->workflow();
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Redis connection refused. password=broker-secret'));
        Bus::shouldReceive('dispatch')->once()->andReturn(null);
        Http::fake(['*' => Http::response([], 200)]);
        $result = resolve(RestErpJobService::class)->execute($group->id);
        $this->assertTrue($result['success']);
        $this->assertSame('success', $group->fresh()->status);
        $this->assertSame('failed', $prints[0]->fresh()->status);
        $this->assertSame('ERP_QUEUE_ERROR', $prints[0]->fresh()->error_code);
        $this->assertNotNull($prints[1]->fresh()->dispatched_at);
        $this->assertStringNotContainsString('broker-secret', json_encode($prints[0]->fresh()->actionLogs()->get()->toArray()));
    }

    public function test_recovery_respects_dependencies_and_handles_worker_loss_after_group_success(): void
    {
        [$group, $prints] = $this->workflow();
        RestErpJob::query()->whereIn('id', array_map(fn ($print) => $print->id, $prints))->update(['queued_at' => now()->subMinutes(3)]);
        $service = resolve(RestErpJobService::class);
        $this->assertSame(0, $service->recover()['published']);
        $group->update(['status' => 'success']);
        $this->assertSame(2, $service->recover()['published']);
        Queue::assertPushed(SendToRestErpJob::class, 3);
        Http::assertNothingSent();
    }

    public function test_dependency_tampering_is_rejected_by_integrity_check(): void
    {
        [$group, $prints] = $this->workflow();
        $group->update(['status' => 'success']);
        $prints[0]->update(['depends_on_id' => null]);
        $result = resolve(RestErpJobService::class)->execute($prints[0]->id);
        $this->assertFalse($result['success']);
        $this->assertSame('ERP_JOB_ERROR', $result['error']['code']);
        Http::assertNothingSent();
    }

    public function test_rollback_cancels_parent_and_dependent_jobs_together(): void
    {
        DB::beginTransaction();
        $this->workflow();
        Queue::assertNothingPushed();
        DB::rollBack();
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        $this->assertDatabaseCount('action_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_existing_migration_can_roll_back_with_dependent_rows(): void
    {
        $this->workflow();
        (require database_path('migrations/2026_10_12_130000_create_rest_erp_jobs_table.php'))->down();
        $this->assertFalse(Schema::hasTable('rest_erp_jobs'));
        $this->assertFalse(Schema::hasTable('rest_erp_job_actors'));
    }

    public function test_dependency_actor_is_checked_and_the_id_is_visible_in_api_and_audit(): void
    {
        [$group, $prints] = $this->workflow();
        $this->actingAs(User::query()->findOrFail(43));
        try {
            resolve(ExitPrintsService::class)->queue('VIN-3', 2, dependsOnId: $group->id);
            $this->fail('Expected actor mismatch.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('rest_erp_jobs', 3);
        }
        Auth::user()->givePermissionTo(Permission::create(['name' => 'erp-jobs', 'guard_name' => 'web']));
        $this->getJson('/api/rest-erp-jobs/'.$prints[0]->id)->assertOk()->assertJsonPath('data.depends_on_id', $group->id);
        $index = $this->getJson('/api/rest-erp-jobs?endpoint=ExitPrints')->assertOk();
        foreach ($index->json('data') as $job) {
            $this->assertSame($group->id, $job['depends_on_id']);
        }
        foreach ($prints[0]->actionLogs()->get() as $log) {
            $this->assertSame($group->id, $log->data['snapshot']['depends_on_id']);
        }
    }
}
