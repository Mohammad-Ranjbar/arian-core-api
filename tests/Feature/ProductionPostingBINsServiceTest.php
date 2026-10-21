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
use Illuminate\Http\Client\ConnectionException;
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
        config()->set('rest_erp.base_url', dirname(self::URL, 3));
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
        $this->assertNull($record->fresh()->data);
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
            'success value' => [['value' => 'Consumption of VIN '.self::VIN.' in WC  is posted.'], 201, 'success', 'Consumption of VIN '.self::VIN.' in WC  is posted.'],
            'success message' => [['message' => 'Posted.'], 200, 'success', 'Posted.'],
            'success without message' => [['posted' => true], 201, 'success', '{"posted":true}'],
            'masked success message' => [['value' => 'Posted. password=private-secret'], 200, 'success', 'Posted. password=[REDACTED]'],
            'empty JSON response' => [[], 200, 'success', ''],
            'business failure' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Provider detail']], 400, 'failed', 'Provider detail'],
            'nested provider message' => [['error' => ['code' => 'Application_DialogException', 'message' => ['value' => 'ERP:ERROR Engine is empty on prod. order component.']]], 400, 'failed', 'ERP:ERROR Engine is empty on prod. order component.'],
            'masked provider error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Refused password=private-secret']], 400, 'failed', 'Refused password=[REDACTED]'],
            'server failure' => [['error' => ['code' => 'InternalError', 'message' => 'Provider failure']], 500, 'uncertain', 'Provider failure'],
        ];
    }

    #[DataProvider('outcomes')]
    public function test_normalized_erp_status_and_message_are_saved_after_completion(array $body, int $httpStatus, string $status, string $message): void
    {
        $record = $this->tracking();
        $result = resolve(ProductionLineTrackingService::class)->sendToErp($record);
        $this->assertNull($record->fresh()->data);
        Event::assertNotDispatched(TrackingEvent::class);
        Http::fake(function () use ($record, $body, $httpStatus) {
            $this->assertNull($record->fresh()->data);
            $this->assertSame('in-progress', RestErpJob::query()->sole()->status);

            return Http::response($body, $httpStatus);
        });

        (new SendToRestErpJob((string) $result['data']['rest_erp_job_id']))->handle(resolve(RestErpJobService::class));

        $this->assertSame($status, RestErpJob::query()->sole()->status);
        $data = $record->fresh()->data;
        $this->assertSame(['status', 'message'], array_keys($data));
        $this->assertSame($status === 'success' ? 'ok' : 'error', $data['status']);
        if ($status === 'success') {
            $this->assertSame($message, $data['message']);
        } else {
            $this->assertStringContainsString($message, $data['message']);
            $this->assertStringContainsString('خطای بیزینس سنترال', $data['message']);
        }
        Event::assertDispatched(TrackingEvent::class);
    }

    public function test_queue_failure_does_not_write_a_response_to_tracking(): void
    {
        $record = $this->tracking();
        $result = resolve(ProductionLineTrackingService::class)->sendToErp($record);
        (new SendToRestErpJob((string) $result['data']['rest_erp_job_id']))->failed(new \RuntimeException('Worker stopped'));

        $this->assertSame('failed', RestErpJob::query()->sole()->status);
        $this->assertNull($record->fresh()->data);
        Event::assertNotDispatched(TrackingEvent::class);
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
        $this->assertNull($body['data']);
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
        $this->assertNull($record->fresh()->data);
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

    public function test_manual_retry_clears_the_previous_response_until_the_next_response_arrives(): void
    {
        $record = $this->tracking();
        resolve(ProductionLineTrackingService::class)->sendToErp($record);
        $jobs = resolve(RestErpJobService::class);
        $job = RestErpJob::query()->sole();
        $errorBody = ['error' => ['code' => 'Application_DialogException', 'message' => 'Refused']];
        $attempt = 0;
        Http::fake(function () use ($record, $errorBody, &$attempt) {
            $this->assertNull($record->fresh()->data);
            $attempt++;

            return $attempt === 1 ? Http::response($errorBody, 400) : Http::response(['posted' => true], 200);
        });
        $jobs->execute($job->id);
        $this->assertSame('error', $record->fresh()->data['status']);
        $this->assertStringContainsString('Refused', $record->fresh()->data['message']);

        $jobs->retry($job->fresh(), Auth::id());

        $this->assertNull($record->fresh()->data);
        $this->assertSame('pending', $job->fresh()->status);
        $jobs->execute($job->id);
        $this->assertSame(['status' => 'ok', 'message' => '{"posted":true}'], $record->fresh()->data);
    }

    public function test_sync_resubmission_clears_the_old_response_before_sending(): void
    {
        $record = $this->tracking();
        $attempt = 0;
        Http::fake(function () use ($record, &$attempt) {
            $this->assertNull($record->fresh()->data);
            $attempt++;

            return $attempt === 1
                ? Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Refused']], 400)
                : Http::response(['posted' => true], 200);
        });
        $service = resolve(ProductionPostingBINsService::class);
        $service->send(self::VIN, '', 'false', 'true', 3, $record)->sync();
        $this->assertNotNull($record->fresh()->data);
        $service->send(self::VIN, '', 'false', 'true', 3, $record)->sync();

        $this->assertSame(['status' => 'ok', 'message' => '{"posted":true}'], $record->fresh()->data);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
    }

    public function test_preflight_failure_does_not_write_tracking_data(): void
    {
        $record = $this->tracking();
        resolve(ProductionLineTrackingService::class)->sendToErp($record);
        $job = RestErpJob::query()->sole();
        $job->update(['payload_hash' => 'invalid']);
        resolve(RestErpJobService::class)->execute($job->id);

        $this->assertNull($record->fresh()->data);
        $this->assertSame('failed', $job->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_ignore_keeps_the_last_erp_response(): void
    {
        $record = $this->tracking();
        resolve(ProductionLineTrackingService::class)->sendToErp($record);
        $jobs = resolve(RestErpJobService::class);
        $body = ['error' => ['code' => 'Application_DialogException', 'message' => 'Refused']];
        Http::fake([self::URL => Http::response($body, 400)]);
        $job = RestErpJob::query()->sole();
        $jobs->execute($job->id);
        $jobs->ignore($job->fresh(), Auth::id());

        $this->assertSame('error', $record->fresh()->data['status']);
        $this->assertStringContainsString('Refused', $record->fresh()->data['message']);
    }

    public function test_timeout_without_response_keeps_tracking_data_empty(): void
    {
        $record = $this->tracking();
        resolve(ProductionLineTrackingService::class)->sendToErp($record);
        Http::fake(function () use ($record) {
            $this->assertNull($record->fresh()->data);
            throw new ConnectionException('Connection timed out');
        });
        resolve(RestErpJobService::class)->execute(RestErpJob::query()->sole()->id);

        $this->assertNull($record->fresh()->data);
        $this->assertSame('uncertain', RestErpJob::query()->sole()->status);
        Event::assertNotDispatched(TrackingEvent::class);
    }

    public function test_clearing_data_rolls_back_with_the_job_transaction(): void
    {
        $record = $this->tracking();
        $record->update(['data' => ['message' => 'Previous result']]);
        DB::beginTransaction();
        resolve(ProductionLineTrackingService::class)->sendToErp($record);
        $this->assertNull($record->fresh()->data);
        DB::rollBack();

        $this->assertSame(['message' => 'Previous result'], $record->fresh()->data);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
    }

    public function test_rejected_job_does_not_write_tracking_data(): void
    {
        $record = $this->tracking();
        $result = resolve(ProductionPostingBINsService::class)->send(self::VIN, '', 'invalid', 'false', 2, $record)->unique();

        $this->assertFalse($result['success']);
        $this->assertNull($record->fresh()->data);
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
