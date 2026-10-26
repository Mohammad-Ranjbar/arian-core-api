<?php

namespace Tests\Feature;

use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\RestErpExecutionMode;
use App\Services\Erp\RestApi\RestErpJobService;
use App\Services\Erp\RestApi\ReturnRequestsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class RestErpExecutionModeTest extends TestCase
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

    public static function serviceModes(): array
    {
        $vin = 'N5RT92CF6SP000481';
        $operations = GroupExitServicesTest::operations();
        foreach ([
            ['CreateSynchroensService', 'CreateSynchroens', ['SX5G-2804-0056', 'P-10-00932', 'LMRLP0100', 'LMRIL0011', '213', 1, '123']],
            ['ExitRequestsService', 'ExitRequests', [$vin, 1, 'DESTINATION', 2]],
            ['ApproveExitZonesService', 'ApproveExitZones', [$vin, 2]],
            ['ApproveReturnZonesService', 'ApproveReturnZones', [$vin, 2]],
            ['CreateTransferZonesService', 'CreateTransferZones', ['ITEM-1', '1', 1, 'Central', 'Production', '', '', 'TEST', 'DOC-1']],
            ['ImportVehiclesService', 'ImportVehicles', [$vin, []]],
            ['ItemReclassJournalsService', 'ItemReclassJournals', ['REF-1', 'BATCH-1', 1, 'TEST']],
            ['SetQcStatusService', 'qcStatuses', [$vin, 84, 2, 1]],
            ['ProductionPostingBINsService', 'ProductionPostingBINs', [$vin, '', 'false', 'false', 2]],
            ['TrackingsService', 'Trackings', [$vin, 'BARCODE-1', 2]],
            ['TransferVehicleProdZonesService', 'TransferVehicleProdZones', [$vin, 'LOCATION-1']],
            ['VehicleTypeBlockingsService', 'VehicleTypeBlockings', [$vin, 'cop']],
            ['VehicleUnblocksService', 'VehicleUnblocks', [$vin]],
        ] as [$class, $endpoint, $arguments]) {
            $operations[$endpoint] = ['App\\Services\\Erp\\RestApi\\'.$class, $endpoint, $arguments];
        }
        $cases = [];
        foreach ($operations as $operation) {
            foreach (RestErpExecutionMode::cases() as $mode) {
                $cases[$operation[1].' '.$mode->value] = [$operation[0], $operation[1], $operation[2], $mode];
            }
        }

        return $cases;
    }

    #[DataProvider('serviceModes')]
    public function test_every_post_service_supports_each_configured_mode(string $service, string $endpoint, array $arguments, RestErpExecutionMode $mode): void
    {
        config()->set('rest_erp.endpoints.'.$endpoint.'.mode', $mode->value);
        Http::fake(['*' => Http::response([], 200)]);

        $result = resolve($service)->dispatch(...$arguments);

        $job = RestErpJob::query()->sole();
        $this->assertTrue($result['success']);
        $this->assertSame($endpoint, $job->endpoint);
        $this->assertSame($job->id, $result['data']['rest_erp_job_id']);
        $this->assertSame($job->uuid, $result['data']['request_id']);
        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $job->section);
        if ($mode === RestErpExecutionMode::Sync) {
            $this->assertSame('sync', $job->execution_mode);
            $this->assertSame('success', $job->status);
            $this->assertNull($job->queue);
            $this->assertNull($job->connection);
            $this->assertSame(200, $result['http_status']);
            Http::assertSentCount(1);
            Queue::assertNothingPushed();
        } else {
            $this->assertSame('queue', $job->execution_mode);
            $this->assertSame('pending', $job->status);
            $this->assertSame($mode->value, $job->queue);
            $this->assertSame('redis', $job->connection);
            $this->assertSame(202, $result['http_status']);
            Queue::assertPushedOn($mode->value, SendToRestErpJob::class);
            Http::assertNothingSent();
        }
    }

    public function test_named_override_takes_precedence_without_changing_configuration(): void
    {
        config()->set('rest_erp.endpoints.ExitPrints.mode', 'parallel');
        $result = resolve(RestErpJobService::class)->dispatch('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2'], mode: RestErpExecutionMode::Unique);

        $this->assertSame(202, $result['http_status']);
        $this->assertSame('unique', RestErpJob::query()->sole()->queue);
        $this->assertSame('parallel', config('rest_erp.endpoints.ExitPrints.mode'));
    }

    public function test_return_requests_requiring_an_immediate_response_stay_sync(): void
    {
        config()->set('rest_erp.endpoints.ReturnRequests.mode', 'unique');
        Http::fake(['*' => Http::response([], 200)]);

        $result = resolve(ReturnRequestsService::class)->sendOrRetry('VIN-1', 'DESTINATION', 2);

        $this->assertTrue($result['success']);
        $this->assertSame('sync', RestErpJob::query()->sole()->execution_mode);
        Http::assertSentCount(1);
        Queue::assertNothingPushed();
    }

    public function test_invalid_mode_fails_before_registering_or_sending(): void
    {
        config()->set('rest_erp.endpoints.ExitPrints.mode', 'typo');
        try {
            resolve(RestErpJobService::class)->dispatch('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2']);
            $this->fail('Invalid execution modes must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('حالت اجرای عملیات', $exception->getMessage());
        }
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_unique_dispatch_waits_for_commit_and_retry_keeps_the_original_queue(): void
    {
        $service = resolve(RestErpJobService::class);
        config()->set('rest_erp.endpoints.ExitPrints.mode', 'unique');
        DB::beginTransaction();
        $result = $service->dispatch('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2']);
        Queue::assertNothingPushed();
        DB::commit();
        Queue::assertPushedOn('unique', SendToRestErpJob::class);
        Http::fake(['*' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Refused.']], 400)]);
        $service->execute($result['data']['rest_erp_job_id']);
        config()->set('rest_erp.endpoints.ExitPrints.mode', 'parallel');

        $retried = $service->retry(RestErpJob::query()->sole(), Auth::id());

        $this->assertSame('unique', $retried->queue);
        $this->assertSame('redis', $retried->connection);
        Queue::assertPushed(SendToRestErpJob::class, 2);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
    }

    public function test_sync_child_rejects_an_unfinished_parent_instead_of_leaving_a_stranded_job(): void
    {
        $service = resolve(RestErpJobService::class);
        $parent = $service->dispatch('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2'], mode: RestErpExecutionMode::Parallel);
        try {
            $service->dispatch('ExitPrints', ['VIN' => 'VIN-2', 'ItemType' => '2'], dependsOnId: $parent['data']['rest_erp_job_id'], mode: RestErpExecutionMode::Sync);
            $this->fail('A synchronous child cannot wait for an asynchronous parent.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('درخواست پیش‌نیاز آن با موفقیت تکمیل شود', $exception->getMessage());
        }
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        Http::assertNothingSent();
    }
}
