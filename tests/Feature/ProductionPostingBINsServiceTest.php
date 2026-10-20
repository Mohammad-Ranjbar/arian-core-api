<?php

namespace Tests\Feature;

use App\Events\TrackingEvent;
use App\Http\Controllers\ProductionTrackingController;
use App\Jobs\SendToRestErpJob;
use App\Models\ProductionLine;
use App\Models\RestErpJob;
use App\Models\SendErp;
use App\Models\Station;
use App\Models\Tracking;
use App\Models\WorkGroupCenter;
use App\Services\Erp\RestApi\ProductionPostingBINsService;
use App\Services\Erp\RestApi\RestErpJobService;
use App\Services\TrackingService\ProductionLineTrackingService;
use App\Services\TrackingService\StationTrackingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class ProductionPostingBINsServiceTest extends TestCase
{
    use CreatesRestErpTables;

    private const URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/ProductionPostingBINs';
    private const VIN = 'N5RL40000S0002132';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('rest_erp.base_url', dirname(self::URL));
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
        Event::fake([TrackingEvent::class]);
        Schema::create('production_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('vin');
            $table->string('type');
            $table->morphs('model');
            $table->unsignedBigInteger('user_id');
            $table->json('data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        (require database_path('migrations/2024_03_02_113319_create_send_erps_table.php'))->up();
    }

    private function tracking(bool $station = false, string $type = 'exit'): Tracking
    {
        $line = new ProductionLine(['item_type' => '3']);
        $line->id = 7;
        $location = $line;
        if ($station) {
            $group = new WorkGroupCenter;
            $group->setRelation('ProductionLine', $line);
            $location = new Station(['code' => 'WC-001']);
            $location->id = 8;
            $location->setRelation('workGroupCenter', $group);
        }
        $record = Tracking::query()->create([
            'vin' => self::VIN, 'type' => $type, 'user_id' => Auth::id(),
            'model_type' => $location->getMorphClass(), 'model_id' => $location->id,
        ]);
        $record->setRelation('model', $location);
        $record->setRelation('user', Auth::user());
        $record->setRelation('vehicle', null);
        $record->setRelation('temporaryProduction', null);

        return $record;
    }

    public function test_sample_payload_is_posted_exactly_and_send_is_lazy(): void
    {
        Http::fake([self::URL => Http::response([], 201)]);
        $pending = resolve(ProductionPostingBINsService::class)->send(self::VIN, '', 'false', 'false', 2);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Http::assertNothingSent();

        $result = $pending->sync();

        $this->assertTrue($result['success']);
        Http::assertSent(fn (Request $request) => $request->url() === self::URL
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === [
                'VIN' => self::VIN, 'WCGroup' => '', 'WithTracking' => 'false',
                'WithOutput' => 'false', 'ItemType' => '2',
            ]);
        $this->assertSame('ثبت عملیات تولید خودرو', RestErpJob::query()->sole()->section);
    }

    public static function trackingCases(): array
    {
        return [
            'line enter' => [false, 'enter', '', 'true'],
            'line exit' => [false, 'exit', '', 'true'],
            'station enter' => [true, 'enter', 'WC-001', 'false'],
            'station exit' => [true, 'exit', 'WC-001', 'true'],
        ];
    }

    #[DataProvider('trackingCases')]
    public function test_tracking_preserves_payload_and_unique_queue_after_commit(bool $station, string $type, string $wcGroup, string $output): void
    {
        $record = $this->tracking($station, $type);
        DB::beginTransaction();
        $service = resolve($station ? StationTrackingService::class : ProductionLineTrackingService::class);
        $result = $service->sendToErp($record);
        $again = $service->sendToErp($record);
        $this->assertSame($result, $again);
        Queue::assertNothingPushed();
        DB::commit();

        $this->assertSame(202, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $data = $record->fresh()->data;
        $this->assertSame('queued', $data['stage']);
        $this->assertSame('pending', $data['job_status']);
        $this->assertArrayNotHasKey('history', $data);
        $this->assertSame($job->id, $data['rest_erp_job_id']);
        $this->assertSame(Tracking::class, $job->model_type);
        $this->assertSame((string) $record->id, (string) $job->model_id);
        $this->assertSame('redis', $job->connection);
        $this->assertSame('unique', $job->queue);
        $this->assertSame([
            'VIN' => self::VIN, 'WCGroup' => $wcGroup, 'WithTracking' => 'false',
            'WithOutput' => $output, 'ItemType' => '3',
        ], $job->parameters);
        Queue::assertPushedOn('unique', SendToRestErpJob::class);
        Queue::assertPushed(SendToRestErpJob::class, 1);
        $this->assertDatabaseCount('erp_jobs', 0);
        Http::assertNothingSent();
    }

    #[DataProvider('trackingCases')]
    public function test_tracking_explicitly_uses_unique_even_if_endpoint_is_configured_sync(bool $station, string $type, string $wcGroup, string $output): void
    {
        config()->set('rest_erp.endpoints.ProductionPostingBINs.mode', 'sync');
        $record = $this->tracking($station, $type);
        $service = resolve($station ? StationTrackingService::class : ProductionLineTrackingService::class);

        $result = $service->sendToErp($record);

        $this->assertSame(202, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('queue', $job->execution_mode);
        $this->assertSame('pending', $job->status);
        $this->assertSame('unique', $job->queue);
        Queue::assertPushedOn('unique', SendToRestErpJob::class);
        Http::assertNothingSent();
        Event::assertNotDispatched(TrackingEvent::class);
    }

    public function test_rollback_discards_job_and_does_not_publish(): void
    {
        DB::beginTransaction();
        resolve(ProductionLineTrackingService::class)->sendToErp($this->tracking());
        DB::rollBack();
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('rest_erp_jobs', 0);
    }

    public static function outcomes(): array
    {
        return [
            'success' => [[], 200, 'success', 'ok', ''],
            'business failure' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Provider detail']], 400, 'failed', 'error', 'Provider detail'],
        ];
    }

    #[DataProvider('outcomes')]
    public function test_worker_updates_tracking_and_notifies_on_completion(array $body, int $httpStatus, string $status, string $legacyStatus, string $message): void
    {
        $record = $this->tracking();
        $result = resolve(ProductionLineTrackingService::class)->sendToErp($record);
        Event::assertNotDispatched(TrackingEvent::class);
        Http::fake(function () use ($record, $body, $httpStatus) {
            $data = $record->fresh()->data;
            $this->assertSame('in-progress', $data['stage']);
            $this->assertSame('in-progress', $data['job_status']);
            $this->assertSame(1, $data['tries']);
            $this->assertArrayNotHasKey('history', $data);

            return Http::response($body, $httpStatus);
        });

        (new SendToRestErpJob((string) $result['data']['rest_erp_job_id']))->handle(resolve(RestErpJobService::class));

        $this->assertSame($status, RestErpJob::query()->sole()->status);
        $data = $record->fresh()->data;
        $this->assertArrayNotHasKey('history', $data);
        $this->assertSame($status, $data['job_status']);
        $this->assertSame($status === 'success', $data['result']['success']);
        $this->assertSame($legacyStatus, $record->fresh()->data['status']);
        $this->assertStringContainsString($message, $record->fresh()->data['message']);
        Event::assertDispatched(TrackingEvent::class);
    }

    public function test_queue_failure_updates_tracking(): void
    {
        $record = $this->tracking();
        $result = resolve(ProductionLineTrackingService::class)->sendToErp($record);
        (new SendToRestErpJob((string) $result['data']['rest_erp_job_id']))->failed(new \RuntimeException('Worker stopped'));

        $this->assertSame('failed', RestErpJob::query()->sole()->status);
        $this->assertSame('error', $record->fresh()->data['status']);
        Event::assertDispatched(TrackingEvent::class);
    }

    public function test_controller_returns_202_and_tracking_ids_and_blocks_duplicate_updates(): void
    {
        $record = $this->tracking(true);
        SendErp::query()->create([
            'model_type' => $record->model_type, 'model_id' => $record->model_id,
            'on_enter' => true, 'on_exit' => true,
        ]);
        config()->set('rest_erp.endpoints.ProductionPostingBINs.mode', 'sync');
        $controller = resolve(ProductionTrackingController::class);
        $response = $controller->update($record);

        $this->assertSame(202, $response->getStatusCode());
        Http::assertNothingSent();
        $this->assertSame('pending', RestErpJob::query()->sole()->status);
        $body = $response->getData(true);
        $this->assertSame(RestErpJob::query()->sole()->id, $body['rest_erp_job_id']);
        $this->assertSame(RestErpJob::query()->sole()->uuid, $body['request_id']);
        $this->assertSame('queued', $body['data']['stage']);
        $this->assertSame(422, $controller->update($record)->getStatusCode());
        Queue::assertPushed(SendToRestErpJob::class, 1);
    }

    public function test_failed_tracking_reuses_the_job_on_resubmission(): void
    {
        $record = $this->tracking();
        $service = resolve(ProductionLineTrackingService::class);
        $first = $service->sendToErp($record);
        Http::fake([self::URL => Http::response([
            'error' => ['code' => 'Application_DialogException', 'message' => 'Refused'],
        ], 400)]);
        resolve(RestErpJobService::class)->execute($first['data']['rest_erp_job_id']);

        $second = $service->sendToErp($record);

        $this->assertSame($first['data'], $second['data']);
        $this->assertArrayNotHasKey('history', $record->fresh()->data);
        $this->assertSame(202, $second['http_status']);
        $this->assertSame('pending', RestErpJob::query()->sole()->status);
        $this->assertSame('unique', RestErpJob::query()->sole()->queue);
        Queue::assertPushed(SendToRestErpJob::class, 2);
    }

    public function test_controller_rolls_back_tracking_changes_when_validation_rejects_the_payload(): void
    {
        $record = $this->tracking();
        $record->update(['data' => ['status' => 'error', 'message' => 'Previous failure']]);
        $record->model->item_type = '';
        SendErp::query()->create([
            'model_type' => $record->model_type, 'model_id' => $record->model_id,
            'on_enter' => true, 'on_exit' => true,
        ]);

        $response = resolve(ProductionTrackingController::class)->update($record);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('VALIDATION_ERROR', $response->getData(true)['error']['code']);
        $this->assertSame('Previous failure', $record->fresh()->data['message']);
        $this->assertSame(0, DB::transactionLevel());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_retry_and_ignore_replace_the_latest_tracking_status(): void
    {
        $record = $this->tracking();
        $record->update(['data' => ['history' => [['stage' => 'old']], 'old_result' => 'discard']]);
        resolve(ProductionLineTrackingService::class)->sendToErp($record);
        $this->assertArrayNotHasKey('old_result', $record->fresh()->data);
        $jobs = resolve(RestErpJobService::class);
        $job = RestErpJob::query()->sole();
        $jobs->fail($job->id, new \RuntimeException('Worker stopped'));
        $jobs->retry($job->fresh(), Auth::id());
        $this->assertSame('queued', $record->fresh()->data['stage']);
        $this->assertNull($record->fresh()->data['result']);
        $this->assertNull($record->fresh()->data['error_code']);
        $this->assertArrayNotHasKey('history', $record->fresh()->data);
        $jobs->fail($job->id, new \RuntimeException('Worker stopped again'));
        $jobs->ignore($job->fresh(), Auth::id());

        $data = $record->fresh()->data;
        $this->assertSame('ignored', $data['stage']);
        $this->assertSame('ignored', $data['job_status']);
        $this->assertArrayNotHasKey('history', $data);
    }

    public function test_uncertain_worker_result_is_saved_as_latest_tracking_status(): void
    {
        $record = $this->tracking();
        resolve(ProductionLineTrackingService::class)->sendToErp($record);
        Http::fake(function () use ($record) {
            $this->assertSame('in-progress', $record->fresh()->data['stage']);
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });
        resolve(RestErpJobService::class)->execute(RestErpJob::query()->sole()->id);

        $data = $record->fresh()->data;
        $this->assertSame('uncertain', $data['job_status']);
        $this->assertSame('error', $data['status']);
        $this->assertSame('ERP_CONNECTION_ERROR', $data['error_code']);
        $this->assertArrayNotHasKey('history', $data);
    }

    public function test_stage_updates_roll_back_with_the_job_transaction(): void
    {
        $record = $this->tracking();
        $record->update(['data' => ['message' => 'Previous result']]);
        DB::beginTransaction();
        resolve(ProductionLineTrackingService::class)->sendToErp($record);
        $this->assertSame('registered', $record->fresh()->data['stage']);
        DB::rollBack();

        $this->assertSame(['message' => 'Previous result'], $record->fresh()->data);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
    }

    public function test_rejected_job_is_recorded_in_tracking_data(): void
    {
        $record = $this->tracking();
        $result = resolve(ProductionPostingBINsService::class)->send(self::VIN, '', 'invalid', 'false', 2, $record)->unique();

        $this->assertFalse($result['success']);
        $data = $record->fresh()->data;
        $this->assertSame('rejected', $data['stage']);
        $this->assertSame('VALIDATION_ERROR', $data['error_code']);
        $this->assertArrayNotHasKey('history', $data);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_invalid_flags_are_rejected_with_persian_validation(): void
    {
        $result = resolve(ProductionPostingBINsService::class)->send(self::VIN, '', 'invalid', 'false', 2)->sync();
        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['http_status']);
        $this->assertStringContainsString('ثبت رهگیری', $result['error']['message']);
        Http::assertNothingSent();
    }
}
