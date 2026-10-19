<?php

namespace Tests\Feature;

use App\Http\Controllers\BasketController;
use App\Http\Requests\UpdateBasketBatchRequest;
use App\Jobs\SendToRestErpJob;
use App\Models\ActionLog;
use App\Models\Basket;
use App\Models\RestErpJob;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Erp\RestApi\CreateTransferZonesService;
use App\Services\Erp\RestApi\ItemReclassJournalsService;
use App\Services\Erp\RestApi\RestErpJobService;
use App\Services\Erp\RestApi\RestErpLogSanitizer;
use App\Services\Erp\RestApi\SetQcStatusService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class RestErpJobTest extends TestCase
{
    use CreatesRestErpTables;

    private const ERP_URL = 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0/ItemReclassJournals';

    private const REMOVED_FIELDS = [
        'client_computer_name', 'request_headers', 'client_ip', 'peer_ip', 'user_agent',
        'response', 'response_headers', 'response_context',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Queue::fake();
        Http::preventStrayRequests();
    }

    private function enqueue(): RestErpJob
    {
        $result = resolve(ItemReclassJournalsService::class)->queue('BKJ1038538', 'Batch-test', 1, 'LAMARI');
        $this->assertSame(202, $result['http_status']);

        return RestErpJob::query()->findOrFail($result['data']['rest_erp_job_id']);
    }

    public static function syncEndpoints(): array
    {
        return [
            'exit' => ['ApproveExitZones', ['BarcodeInfo' => 'VIN-1', 'ItemType' => '2']],
            'return' => ['ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '2']],
            'qc' => ['qcStatuses', ['VIN' => 'A1234567890123456', 'QCStatus' => '84', 'ItemType' => '2', 'QCType' => '1']],
            'basket' => ['ItemReclassJournals', ['ArrayCrossReferenceNo' => 'BK-1', 'BatchNo' => 'TEST', 'Number' => '1', 'BatchName' => 'LAMARI']],
            'transfer' => ['CreateTransferZones', [
                'ItemNoArray' => 'ITEM-1', 'ItemQtyArray' => '1', 'NumberItems' => '1',
                'FromLocationType' => 'Central', 'ToLacationType' => 'Production', 'VariantArray' => '',
                'CrossRefArray' => '', 'MakeCode' => 'LAMARI', 'DocumentNo' => '77',
            ]],
        ];
    }

    #[DataProvider('syncEndpoints')]
    public function test_repeated_sync_send_reuses_the_failed_job_and_preserves_attempt_history(string $endpoint, array $payload): void
    {
        Http::fakeSequence()
            ->push(['error' => ['code' => 'Application_DialogException', 'message' => 'First refusal.']], 400)
            ->push(['error' => ['code' => 'Application_DialogException', 'message' => 'Second refusal.']], 400)
            ->push([], 200);
        $service = resolve(RestErpJobService::class);
        $service->send($endpoint, $payload, Auth::user());
        $original = RestErpJob::query()->sole();
        $this->assertFalse($service->send($endpoint, $payload, Auth::user())['success']);
        $this->assertTrue($service->send($endpoint, $payload, Auth::user())['success']);
        $job = RestErpJob::query()->sole();
        $this->assertSame($original->id, $job->id);
        $this->assertSame($original->uuid, $job->uuid);
        $this->assertSame(3, $job->tries);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame('success', $job->status);
        $this->assertSame(42, $job->last_retry_user_id);
        $this->assertNotNull($job->last_retry_at);
        $retries = $job->actionLogs()->whereIn('action', ['rest_erp_failed', 'rest_erp_success'])->oldest('id')->get();
        $this->assertSame(["\u{2067}خطای بیزینس سنترال :: \u{2066}First refusal. \u{2069}\u{2069}", "\u{2067}خطای بیزینس سنترال :: \u{2066}Second refusal. \u{2069}\u{2069}"], $retries->where('action', 'rest_erp_failed')->pluck('data.snapshot.description')->values()->all());
        $this->assertSame(1, $job->actionLogs()->where('action', 'rest_erp_created')->count());
        Http::assertSentCount(3);
        Queue::assertNothingPushed();
    }

    public function test_uncertain_sync_send_requires_explicit_retry_and_keeps_sync_execution(): void
    {
        Http::fakeSequence()->push([], 500)->push([], 200);
        $service = resolve(RestErpJobService::class);
        $payload = ['VIN' => 'A1234567890123456', 'QCStatus' => '84', 'ItemType' => '2', 'QCType' => '1'];
        $service->send('qcStatuses', $payload, Auth::user());
        $job = RestErpJob::query()->sole();
        $result = $service->send('qcStatuses', $payload, Auth::user());
        $this->assertSame(409, $result['http_status']);
        $this->assertSame('ERP_EXECUTION_UNCERTAIN', $result['error']['code']);
        $this->assertSame($job->id, $result['rest_erp_job_id']);
        Http::assertSentCount(1);
        $retried = $service->retry($job, 42, true);
        $this->assertSame($job->id, $retried->id);
        $this->assertSame('sync', $retried->execution_mode);
        $this->assertSame('success', $retried->status);
        $this->assertSame(2, $retried->tries);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        Http::assertSentCount(2);
        Queue::assertNothingPushed();
    }

    public function test_repeated_sync_send_does_not_duplicate_an_in_progress_job(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Refused.']], 400)]);
        $service = resolve(RestErpJobService::class);
        $payload = ['VIN' => 'VIN-1', 'ItemType' => '2'];
        $service->send('ApproveReturnZones', $payload, Auth::user());
        $job = RestErpJob::query()->sole();
        $job->update(['status' => 'in-progress']);
        $result = $service->send('ApproveReturnZones', $payload, Auth::user());
        $this->assertSame(409, $result['http_status']);
        $this->assertSame('ERP_JOB_IN_PROGRESS', $result['error']['code']);
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        Http::assertSentCount(1);
    }

    public function test_changed_sync_parameters_reuse_the_entity_job_but_a_different_model_gets_its_own_job(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Refused.']], 400)]);
        $service = resolve(RestErpJobService::class);
        $service->send('ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '2'], Auth::user());
        $service->send('ApproveReturnZones', ['VIN' => 'VIN-2', 'ItemType' => '2'], Auth::user());
        $service->send('ApproveReturnZones', ['VIN' => 'VIN-2', 'ItemType' => '2'], User::query()->findOrFail(43));
        $this->assertDatabaseCount('rest_erp_jobs', 2);
        $this->assertSame([2, 1], RestErpJob::query()->orderBy('id')->pluck('tries')->all());
        Http::assertSentCount(3);
    }

    public function test_qc_status_and_actor_changes_reuse_one_vehicle_job_across_sync_and_queue(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->softDeletes();
        });
        DB::table('vehicles')->insert(['vin' => 'A1234567890123456']);
        $vehicle = Vehicle::without(['carModel', 'makeCode'])->findOrFail('A1234567890123456');
        Http::fakeSequence()
            ->push(['error' => ['code' => 'Application_DialogException', 'message' => 'QC refused.']], 400)
            ->push([], 200)->push([], 200)->push([], 200);
        $service = resolve(SetQcStatusService::class);
        $this->assertFalse($service->send($vehicle->vin, 84, 2, 1, $vehicle)['success']);
        $original = RestErpJob::query()->sole();
        $this->actingAs(User::query()->findOrFail(43));
        $this->assertTrue($service->send($vehicle->vin, 85, 2, 1, $vehicle)['success']);
        $this->assertTrue($service->send($vehicle->vin, 24, 1, 2, $vehicle)['success']);
        $queued = $service->queue($vehicle->vin, 25, 1, 2, $vehicle);
        $duplicate = $service->queue($vehicle->vin, 25, 1, 2, $vehicle);
        $this->assertSame($original->id, $queued['data']['rest_erp_job_id']);
        $this->assertSame($queued['data'], $duplicate['data']);
        Queue::assertPushed(SendToRestErpJob::class, 1);
        $job = RestErpJob::query()->sole();
        $this->assertSame($original->uuid, $job->uuid);
        $this->assertSame('pending', $job->status);
        $this->assertSame(['VIN' => $vehicle->vin, 'QCStatus' => '25', 'ItemType' => '1', 'QCType' => '2'], $job->parameters);
        resolve(RestErpJobService::class)->execute($job->id);
        $job->refresh();
        $this->assertSame('success', $job->status);
        $this->assertSame(4, $job->tries);
        $this->assertSame(42, $job->user_id);
        $this->assertSame(43, $job->last_retry_user_id);
        $history = $job->actionLogs()->whereIn('action', ['rest_erp_failed', 'rest_erp_success'])->oldest('id')->get();
        $this->assertSame(['84', '85', '24', '25'], $history->pluck('data.snapshot.parameters.QCStatus')->all());
        $this->assertSame("\u{2067}خطای بیزینس سنترال :: \u{2066}QC refused. \u{2069}\u{2069}", $history->first()->data['snapshot']['description']);
        Http::assertSentCount(4);
    }

    public function test_rejected_payload_is_corrected_on_the_same_entity_operation_job(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $service = resolve(RestErpJobService::class);
        $invalid = ['VIN' => 'A1234567890123456', 'QCStatus' => '', 'ItemType' => '2', 'QCType' => '1'];
        $this->assertSame(422, $service->send('qcStatuses', $invalid, Auth::user())['http_status']);
        $original = RestErpJob::query()->sole();
        $this->assertSame(422, $service->send('qcStatuses', $invalid, Auth::user())['http_status']);
        $payload = [...$invalid, 'QCStatus' => '84'];
        $this->assertTrue($service->send('qcStatuses', $payload, Auth::user(), 'changed-section')['success']);
        $job = RestErpJob::query()->sole();
        $this->assertSame($original->id, $job->id);
        $this->assertSame($original->uuid, $job->uuid);
        $this->assertSame($payload, $job->parameters);
        $this->assertSame(1, $job->tries);
        $this->assertSame('changed-section', $job->section);
        Http::assertSentCount(1);
    }

    public function test_different_operations_on_the_same_entity_keep_separate_stable_jobs(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $service = resolve(RestErpJobService::class);
        $service->send('ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '2'], Auth::user());
        $service->send('ApproveExitZones', ['BarcodeInfo' => 'VIN-1', 'ItemType' => '2'], Auth::user());
        $service->send('ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '1'], Auth::user());
        $this->assertDatabaseCount('rest_erp_jobs', 2);
        $this->assertSame([2, 1], RestErpJob::query()->orderBy('id')->pluck('tries')->all());
        Http::assertSentCount(3);
    }

    public function test_queue_reexecution_after_failure_updates_the_same_entity_job_parameters(): void
    {
        $service = resolve(RestErpJobService::class);
        $queued = $service->enqueue('ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '2'], Auth::user());
        Http::fakeSequence()
            ->push(['error' => ['code' => 'Application_DialogException', 'message' => 'Return refused.']], 400)
            ->push([], 200);
        $service->execute($queued['data']['rest_erp_job_id']);
        $retried = $service->enqueue('ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '1'], Auth::user());
        $this->assertSame($queued['data'], $retried['data']);
        $service->execute($retried['data']['rest_erp_job_id']);
        $job = RestErpJob::query()->sole();
        $this->assertSame(2, $job->tries);
        $this->assertSame('success', $job->status);
        $this->assertSame('1', $job->parameters['ItemType']);
        $this->assertSame('2', $job->actionLogs()->where('action', 'rest_erp_failed')->sole()->data['snapshot']['parameters']['ItemType']);
        Queue::assertPushed(SendToRestErpJob::class, 2);
        Http::assertSentCount(2);
    }

    public static function activeJobStatuses(): array
    {
        return [['pending'], ['in-progress'], ['uncertain']];
    }

    #[DataProvider('activeJobStatuses')]
    public function test_changed_parameters_cannot_replace_an_active_or_uncertain_entity_job(string $status): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Refused.']], 400)]);
        $service = resolve(RestErpJobService::class);
        $service->send('ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '2'], Auth::user());
        $job = RestErpJob::query()->sole();
        $job->update(['status' => $status]);
        foreach ([false, true] as $queued) {
            $result = $queued
                ? $service->enqueue('ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '1'], Auth::user())
                : $service->send('ApproveReturnZones', ['VIN' => 'VIN-1', 'ItemType' => '1'], Auth::user());
            $this->assertSame(409, $result['http_status']);
            $this->assertSame($job->id, $result['rest_erp_job_id']);
        }
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        $this->assertSame('2', $job->fresh()->parameters['ItemType']);
        Http::assertSentCount(1);
        Queue::assertNothingPushed();
    }

    public function test_unlinked_sync_jobs_are_reused_only_for_the_same_actor(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'Application_DialogException', 'message' => 'Refused.']], 400)]);
        $service = resolve(RestErpJobService::class);
        $payload = ['VIN' => 'VIN-1', 'ItemType' => '2'];
        $service->send('ApproveReturnZones', $payload);
        $service->send('ApproveReturnZones', $payload);
        $this->assertSame(2, RestErpJob::query()->sole()->tries);
        $service->send('ApproveReturnZones', $payload, userId: 43);
        $this->assertDatabaseCount('rest_erp_jobs', 2);
        Http::assertSentCount(3);
    }

    public function test_baskets_are_registered_in_an_independent_parallel_job_without_http(): void
    {
        DB::table('baskets')->insert([
            ['id' => 1, 'code' => 'BKJ1038538', 'user_id' => 42, 'status' => 'new'],
            ['id' => 2, 'code' => 'BKJ1038539', 'user_id' => 42, 'status' => 'new'],
            ['id' => 3, 'code' => 'OTHER', 'user_id' => 43, 'status' => 'new'],
        ]);
        $request = Mockery::mock(UpdateBasketBatchRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['batch' => 'test', 'batch_name' => 'LAMARI']);
        $response = resolve(BasketController::class)->batchUpdate($request);
        $this->assertSame(202, $response->getStatusCode());
        $job = RestErpJob::query()->sole();
        $this->assertSame($job->id, $response->getData(true)['rest_erp_job_id']);
        $this->assertSame('pending', $job->status);
        $this->assertSame(0, $job->tries);
        $this->assertSame(Basket::class, $job->model_type);
        $this->assertSame('1', $job->model_id);
        $this->assertSame(42, $job->user_id);
        $this->assertSame(['ArrayCrossReferenceNo' => 'BKJ1038538,BKJ1038539', 'BatchNo' => 'Batch-test', 'Number' => '2', 'BatchName' => 'LAMARI'], $job->parameters);
        $this->assertSame('submitted', DB::table('baskets')->where('id', 1)->value('status'));
        $this->assertSame('submitted', DB::table('baskets')->where('id', 2)->value('status'));
        $this->assertSame('new', DB::table('baskets')->where('id', 3)->value('status'));
        $this->assertSame(0, DB::table('erp_jobs')->count());
        $this->assertFalse(Schema::hasColumn('erp_jobs', 'transport'));
        Queue::assertPushedOn('parallel', SendToRestErpJob::class, fn ($queued) => $queued->id === (string) $job->id && $queued->connection === 'redis' && $queued->tries === 1 && $queued->timeout === 30);
        Http::assertNothingSent();
        $this->assertSame(['rest_erp_created'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
    }

    public function test_context_redacts_secrets_without_collecting_removed_client_metadata(): void
    {
        $request = Request::create('/api/basket-batch-update?access_token=query-secret', 'POST', [
            'password' => 'password-secret', 'nested' => ['api_key' => 'key-secret', 'ordinary' => "hello\r\nworld"],
        ], [], [], [
            'REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
            'HTTP_USER_AGENT' => 'ExampleBrowser/1.0', 'HTTP_X_CLIENT_COMPUTER_NAME' => "PC-12\r\nforged",
            'HTTP_AUTHORIZATION' => 'Bearer auth-secret', 'HTTP_COOKIE' => 'session=private-session',
            'HTTP_REFERER' => 'https://example.test/account?token=ref-secret',
            'HTTP_ACCEPT_LANGUAGE' => 'header-only-marker',
        ]);
        $this->app->instance('request', $request);
        $job = $this->enqueue();
        foreach ([...self::REMOVED_FIELDS, 'headers', 'client_ips', 'computer_name_source'] as $field) {
            $this->assertArrayNotHasKey($field, $job->toArray());
            $this->assertArrayNotHasKey($field, $job->request_context);
        }
        $this->assertSame('https://example.test', $job->request_context['referer_origin']);
        $this->assertSame('[REDACTED]', $job->request_context['input']['password']);
        $this->assertSame('helloworld', $job->request_context['input']['nested']['ordinary']);
        $serialized = json_encode([$job->toArray(), $job->actionLogs()->get()->toArray()]);
        foreach (['query-secret', 'password-secret', 'key-secret', 'auth-secret', 'private-session', 'ref-secret', '203.0.113.9', '10.0.0.1', 'PC-12', 'ExampleBrowser/1.0', 'header-only-marker'] as $secret) {
            $this->assertStringNotContainsString($secret, $serialized);
        }
        $this->assertSame($job->parameters, json_decode(DB::table('rest_erp_jobs')->value('parameters'), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame($job->parameters, $job->toArray()['parameters']);
        $this->assertArrayNotHasKey('payload_hash', $job->toArray());
    }

    public function test_removed_fields_are_absent_from_the_schema_job_and_action_api(): void
    {
        foreach (self::REMOVED_FIELDS as $field) {
            $this->assertFalse(Schema::hasColumn('rest_erp_jobs', $field));
        }
        Auth::user()->givePermissionTo(Permission::create(['name' => 'erp-jobs', 'guard_name' => 'web']));
        $job = $this->enqueue();
        Http::fake([self::ERP_URL => Http::response(['body' => 'response-body-marker'], 201, ['X-Request-ID' => 'response-header-marker'])]);
        resolve(RestErpJobService::class)->execute($job->id);
        $show = $this->getJson('/api/rest-erp-jobs/'.$job->id)->assertOk();
        $index = $this->getJson('/api/rest-erp-jobs')->assertOk();
        $actions = $this->getJson('/api/rest-erp-jobs/'.$job->id.'/actions')->assertOk();
        foreach (self::REMOVED_FIELDS as $field) {
            $show->assertJsonMissingPath('data.'.$field);
            $index->assertJsonMissingPath('data.0.'.$field);
            foreach ($actions->json('data') as $log) {
                $this->assertArrayNotHasKey($field, $log['data']);
                $this->assertArrayNotHasKey($field, $log['data']['snapshot']);
            }
        }
        $serialized = json_encode([$job->fresh()->toArray(), $job->actionLogs()->get()->toArray()]);
        $this->assertStringNotContainsString('response-body-marker', $serialized);
        $this->assertStringNotContainsString('response-header-marker', $serialized);
    }

    public function test_sync_qc_requests_are_also_audited_and_return_the_original_contract(): void
    {
        Http::fake(['*/qcStatuses' => Http::response(['VIN' => 'A1234567890123456', 'access_token' => 'response-secret'], 200, ['Set-Cookie' => 'private-cookie', 'X-Request-ID' => 'provider-1'])]);
        $result = resolve(SetQcStatusService::class)->send('A1234567890123456', 84, 1, 1);
        $job = RestErpJob::query()->sole();
        $this->assertTrue($result['success']);
        $this->assertSame(200, $result['http_status']);
        $this->assertSame('success', $job->status);
        $this->assertSame('sync', $job->execution_mode);
        $this->assertSame(200, $job->provider_http_status);
        $this->assertSame('A1234567890123456', $result['data']['VIN']);
        foreach (self::REMOVED_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $job->toArray());
        }
        $this->assertStringNotContainsString('response-secret', json_encode($job->actionLogs()->get()->toArray()));
        $this->assertStringNotContainsString('provider-1', json_encode($job->actionLogs()->get()->toArray()));
        $this->assertStringNotContainsString('private-cookie', json_encode($job->actionLogs()->get()->toArray()));
        Queue::assertNothingPushed();
    }

    #[DataProvider('erpResponses')]
    public function test_workers_persist_each_outcome_and_its_audit_trail($body, int $providerStatus, string $status, int $httpStatus, ?string $error): void
    {
        Http::fake([self::ERP_URL => Http::response($body, $providerStatus)]);
        $job = $this->enqueue();
        resolve(RestErpJobService::class)->execute($job->id);
        $job->refresh();
        $this->assertSame($status, $job->status);
        $this->assertSame($providerStatus, $job->provider_http_status);
        $this->assertSame($httpStatus, $job->http_status);
        $this->assertSame($error, $job->error_code);
        $this->assertSame(1, $job->tries);
        $this->assertNotNull($job->started_at);
        $this->assertNotNull($job->completed_at);
        $this->assertGreaterThanOrEqual(0, $job->execute_time);
        $this->assertSame(['rest_erp_created', 'rest_erp_'.$status], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $request) => $request->url() === self::ERP_URL && $request->hasHeader('X-Request-ID', $job->uuid) && $request->data() === $job->parameters);
    }

    public static function erpResponses(): array
    {
        return [
            'success' => [['Number' => '1'], 201, 'success', 201, null],
            'business error' => [['error' => ['code' => 'Application_DialogException', 'message' => 'Rejected']], 400, 'failed', 422, 'Application_DialogException'],
            'authentication' => [[], 401, 'failed', 502, 'ERP_AUTH_ERROR'],
            'server error' => [[], 500, 'uncertain', 502, 'ERP_ERROR'],
            'invalid success body' => ['not-json', 200, 'uncertain', 502, 'ERP_ERROR'],
        ];
    }

    public function test_duplicate_execution_of_a_successful_or_processing_job_does_not_send_twice(): void
    {
        $job = $this->enqueue();
        Http::fake(function () use ($job) {
            $this->assertNull(resolve(RestErpJobService::class)->execute($job->id));

            return Http::response(['Number' => '1'], 201);
        });
        $service = resolve(RestErpJobService::class);
        $service->execute($job->id);
        $this->assertNull($service->execute($job->id));
        Http::assertSentCount(1);
        $this->assertSame(2, $job->actionLogs()->count());
    }

    public function test_dispatch_waits_for_commit_and_rollback_removes_the_job_and_audits(): void
    {
        DB::beginTransaction();
        $this->enqueue();
        Queue::assertNothingPushed();
        DB::rollBack();
        $this->assertSame(0, RestErpJob::query()->count());
        $this->assertSame(0, ActionLog::query()->count());
        Queue::assertNothingPushed();
        DB::transaction(function () {
            $this->enqueue();
            Queue::assertNothingPushed();
        });
        Queue::assertPushed(SendToRestErpJob::class, 1);
    }

    public function test_invalid_input_is_rejected_and_audited_without_queue_or_http(): void
    {
        $result = resolve(ItemReclassJournalsService::class)->queue('', 'test', 0, 'LAMARI');
        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertNotEmpty($result['error']['message']);
        $this->assertSame(422, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('rejected', $job->status);
        $this->assertSame([], $job->parameters);
        $this->assertSame(['rest_erp_created'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_sync_invalid_input_preserves_the_error_contract_without_a_stored_response(): void
    {
        $result = resolve(ItemReclassJournalsService::class)->send('', 'test', 0, 'LAMARI');
        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertSame('VALIDATION_ERROR', $result['error']['code']);
        $this->assertNotEmpty($result['error']['message']);
        $this->assertSame(422, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('rejected', $job->status);
        $this->assertArrayNotHasKey('response', $job->toArray());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_connection_failures_require_acknowledgement_before_manual_retry(): void
    {
        $attempt = 0;
        Http::fake(function () use (&$attempt) {
            if ($attempt++ === 0) {
                throw new ConnectionException('Bearer connection-secret');
            }

            return Http::response(['Number' => '1'], 201);
        });
        $job = $this->enqueue();
        $service = resolve(RestErpJobService::class);
        $service->execute($job->id);
        $job->refresh();
        $this->assertSame('uncertain', $job->status);
        try {
            $service->retry($job, 43);
            $this->fail('An uncertain request must require acknowledgement.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('acknowledge_uncertain_result', $exception->errors());
        }
        $service->retry($job, 43, true);
        $service->execute($job->id);
        $job->refresh();
        $this->assertSame('success', $job->status);
        $this->assertSame(2, $job->tries);
        $this->assertSame(43, $job->last_retry_user_id);
        $this->assertDatabaseHas('rest_erp_job_actors', ['rest_erp_job_id' => $job->id, 'user_id' => 43]);
        $this->assertSame('uncertain', $job->actionLogs()->where('action', 'rest_erp_uncertain')->sole()->data['snapshot']['status']);
        $this->assertSame(['rest_erp_created', 'rest_erp_uncertain', 'rest_erp_success'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        $this->assertStringNotContainsString('connection-secret', json_encode($job->actionLogs()->get()->toArray()));
    }

    public function test_broker_failures_are_recorded_without_disclosing_exception_secrets(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('password=broker-secret'));
        try {
            $this->enqueue();
            $this->fail('Expected a broker exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('broker-secret', $exception->getMessage());
            $job = RestErpJob::query()->sole();
            $this->assertSame('failed', $job->status);
            $this->assertSame('ERP_QUEUE_ERROR', $job->error_code);
            $this->assertSame(0, $job->tries);
            $this->assertStringNotContainsString('broker-secret', json_encode($job->actionLogs()->get()->toArray()));
        }
        Http::assertNothingSent();
    }

    public function test_worker_failure_records_an_uncertain_outcome_and_keeps_its_history(): void
    {
        $job = $this->enqueue();
        $job->update(['status' => 'in-progress', 'tries' => 1, 'started_at' => now()]);
        (new SendToRestErpJob((string) $job->id))->failed(new RuntimeException('password=worker-secret'));
        $this->assertSame('uncertain', $job->fresh()->status);
        $this->assertSame('ERP_EXECUTION_UNCERTAIN', $job->fresh()->error_code);
        $this->assertStringNotContainsString('worker-secret', json_encode($job->actionLogs()->get()->toArray()));
    }

    public function test_payload_tampering_and_url_changes_are_rejected_before_http(): void
    {
        $service = resolve(RestErpJobService::class);
        $job = $this->enqueue();
        $job->update(['parameters' => ['ArrayCrossReferenceNo' => 'CHANGED', 'BatchNo' => 'test', 'Number' => '1', 'BatchName' => 'LAMARI']]);
        $service->execute($job->id);
        $this->assertSame('ERP_JOB_ERROR', $job->fresh()->error_code);
        $other = $this->enqueue();
        $other->update(['url' => 'http://attacker.invalid/']);
        $service->execute($other->id);
        $this->assertSame('ERP_JOB_ERROR', $other->fresh()->error_code);
        Http::assertNothingSent();
    }

    public function test_audit_failure_rolls_back_registration_before_sending(): void
    {
        DB::statement("CREATE TRIGGER reject_rest_audit BEFORE INSERT ON action_logs BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        try {
            $this->enqueue();
            $this->fail('Expected audit persistence to fail.');
        } catch (QueryException $exception) {
            $this->assertSame(0, RestErpJob::query()->count());
            $this->assertSame(0, DB::table('rest_erp_job_actors')->count());
        }
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_large_payloads_and_responses_have_bounded_audits(): void
    {
        config()->set('rest_erp.max_payload_bytes', 50);
        $result = resolve(ItemReclassJournalsService::class)->queue(str_repeat('X', 1000), 'test', 1, 'LAMARI');
        $this->assertSame(422, $result['http_status']);
        config()->set('rest_erp.max_payload_bytes', 262144);
        config()->set('rest_erp.max_response_bytes', 20);
        Http::fake([self::ERP_URL => Http::response(['data' => str_repeat('X', 100)], 201)]);
        $job = $this->enqueue();
        resolve(RestErpJobService::class)->execute($job->id);
        $this->assertSame('uncertain', $job->fresh()->status);
        $this->assertSame('ERP_RESPONSE_TOO_LARGE', $job->fresh()->error_code);
        $sanitizer = resolve(RestErpLogSanitizer::class);
        $safe = $sanitizer->sanitize(['credentials' => ['username' => 'hidden', 'password' => 'secret'], 'items' => array_fill(0, 1000, str_repeat('Y', 10000))]);
        $this->assertSame('[REDACTED]', $safe['credentials']);
        $this->assertLessThan(70000, strlen(json_encode($safe)));
        $unicode = $sanitizer->sanitize(array_fill(0, 1000, str_repeat('سلام"', 2000)));
        $this->assertLessThanOrEqual(65536, strlen(json_encode($unicode)));
    }

    public function test_audit_actors_cannot_be_hard_deleted_and_logs_survive_soft_deletion(): void
    {
        $job = $this->enqueue();
        $job->update(['status' => 'failed']);
        resolve(RestErpJobService::class)->retry($job, 43);
        foreach ([42, 43] as $actor) {
            try {
                DB::table('users')->where('id', $actor)->delete();
                $this->fail('Audit actors must be retained.');
            } catch (QueryException $exception) {
                $this->assertTrue(DB::table('users')->where('id', $actor)->exists());
            }
        }
        User::query()->findOrFail(42)->delete();
        $this->assertNotNull($job->user);
        $this->assertGreaterThan(0, $job->actionLogs()->count());
    }

    public function test_job_and_action_endpoints_enforce_access_control(): void
    {
        $job = $this->enqueue();
        $this->getJson('/api/rest-erp-jobs')->assertForbidden();
        $this->getJson('/api/rest-erp-jobs/'.$job->id)->assertForbidden();
        $this->getJson('/api/rest-erp-jobs/'.$job->id.'/actions')->assertForbidden();
        $this->putJson('/api/rest-erp-jobs/'.$job->id.'/retry')->assertForbidden();
    }

    public function test_authorized_endpoints_expose_safe_metadata_and_validate_pagination(): void
    {
        foreach (['erp-jobs', 'retry-erp-jobs'] as $ability) {
            Auth::user()->givePermissionTo(Permission::create(['name' => $ability, 'guard_name' => 'web']));
        }
        $job = $this->enqueue();
        $this->getJson('/api/rest-erp-jobs/'.$job->id)->assertOk()->assertJsonPath('data.uuid', $job->uuid)
            ->assertJsonPath('data.parameters', $job->parameters)->assertJsonMissingPath('data.payload_hash');
        $this->getJson('/api/rest-erp-jobs')->assertOk()->assertJsonPath('data.0.parameters', $job->parameters);
        $this->getJson('/api/rest-erp-jobs/'.$job->id.'/actions')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/rest-erp-jobs?per_page=1000')->assertUnprocessable();
        $this->putJson('/api/rest-erp-jobs/'.$job->id.'/retry')->assertUnprocessable();
        $job->update(['status' => 'failed']);
        $this->putJson('/api/rest-erp-jobs/'.$job->id.'/retry')->assertStatus(202);
        $this->assertSame('pending', $job->fresh()->status);
    }

    public function test_ignore_preserves_audits_and_stale_queued_jobs_do_not_execute(): void
    {
        $job = $this->enqueue();
        $job->update(['status' => 'failed']);
        resolve(RestErpJobService::class)->ignore($job, 43);
        $this->assertSame('ignored', $job->fresh()->status);
        $this->assertNull(resolve(RestErpJobService::class)->execute($job->id));
        $this->assertSame(['rest_erp_created'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        Http::assertNothingSent();
    }

    public function test_recovery_publishes_only_undispatched_requests_and_does_not_replay_stale_executions(): void
    {
        $undispatched = $this->enqueue();
        $undispatched->update(['dispatched_at' => null, 'queued_at' => now()->subMinutes(3)]);
        $backlog = $this->enqueue();
        $backlog->update(['queued_at' => now()->subHour()]);
        $stale = $this->enqueue();
        $stale->update(['status' => 'in-progress', 'tries' => 1, 'started_at' => now()->subMinutes(6)]);
        $active = $this->enqueue();
        $active->update(['status' => 'in-progress', 'tries' => 1, 'started_at' => now()]);
        Queue::fake();

        $counts = resolve(RestErpJobService::class)->recover();

        $this->assertSame(['published' => 1, 'uncertain' => 1, 'publish_failed' => 0], $counts);
        $this->assertNotNull($undispatched->fresh()->dispatched_at);
        $this->assertSame('pending', $backlog->fresh()->status);
        $this->assertSame('uncertain', $stale->fresh()->status);
        $this->assertSame('in-progress', $active->fresh()->status);
        Queue::assertPushed(SendToRestErpJob::class, 1);
        Queue::assertPushed(SendToRestErpJob::class, fn ($queued) => $queued->id === (string) $undispatched->id);
        Http::assertNothingSent();
        $this->artisan('rest-erp:recover')->assertExitCode(0);
    }

    public function test_corrupt_encrypted_payloads_fail_safely_without_leaking_or_sending(): void
    {
        $job = $this->enqueue();
        DB::table('rest_erp_jobs')->where('id', $job->id)->update(['parameters' => 'invalid-ciphertext']);
        resolve(RestErpJobService::class)->execute($job->id);
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame('ERP_JOB_ERROR', $job->fresh()->error_code);
        $this->assertNull($job->fresh()->toArray()['parameters']);
        $this->assertStringNotContainsString('invalid-ciphertext', json_encode($job->actionLogs()->get()->toArray()));
        Http::assertNothingSent();
    }

    public function test_endpoint_configuration_rejects_arbitrary_destinations_and_credentials_and_accepts_internal_http(): void
    {
        $service = resolve(RestErpJobService::class);
        foreach ([
            'http://attacker.invalid/PreBC',
            'http://user:password@172.16.0.154:7048/PreBC',
            self::ERP_URL.'?token=secret',
            self::ERP_URL.'#fragment',
        ] as $url) {
            config()->set('rest_erp.base_url', $url);
            try {
                $service->enqueue('ItemReclassJournals', []);
                $this->fail('Invalid destination configuration must be rejected.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertSame('Invalid ERP base URL configuration.', $exception->getMessage());
            }
        }
        config()->set('rest_erp.base_url', 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0');
        $result = $service->enqueue('ItemReclassJournals', [
            'ArrayCrossReferenceNo' => 'BKJ1038538', 'BatchNo' => 'test', 'Number' => '1', 'BatchName' => 'LAMARI',
        ]);
        $this->assertSame(202, $result['http_status']);
        $this->assertSame(self::ERP_URL, RestErpJob::query()->sole()->url);
    }

    public function test_all_existing_rest_services_support_queued_execution_with_the_same_worker(): void
    {
        Http::fake(['*' => Http::response(['accepted' => true], 201)]);
        $qc = resolve(SetQcStatusService::class)->queue('A1234567890123456', 84, 1, 1);
        $transfer = resolve(CreateTransferZonesService::class)->queue('ITEM-1', '1.500', 1, 'Central', 'Production', '', '', 'LAMARI', 'ALT-77');
        Http::assertNothingSent();
        Queue::assertPushed(SendToRestErpJob::class, 2);
        foreach ([$qc, $transfer] as $result) {
            $this->app->call([new SendToRestErpJob((string) $result['data']['rest_erp_job_id']), 'handle']);
        }
        Http::assertSentCount(2);
        $job = RestErpJob::query()->where('endpoint', 'CreateTransferZones')->sole();
        $this->assertSame('success', $job->status);
        $this->assertSame('1.500', $job->parameters['ItemQtyArray']);
        $this->assertSame('', $job->parameters['VariantArray']);
        $this->assertSame('Production', $job->parameters['ToLacationType']);
    }

    public function test_a_late_result_cannot_overwrite_a_newer_attempt(): void
    {
        $job = $this->enqueue();
        Http::fake(function () use ($job) {
            // Simulate a newer attempt winning the claim while the older result is in transit.
            $job->update(['tries' => 2, 'status' => 'in-progress']);

            return Http::response(['Number' => '1'], 201);
        });
        resolve(RestErpJobService::class)->execute($job->id);
        $this->assertSame(2, $job->fresh()->tries);
        $this->assertSame('in-progress', $job->fresh()->status);
        $this->assertArrayNotHasKey('response', $job->fresh()->toArray());
        $this->assertSame(['rest_erp_created'], $job->actionLogs()->orderBy('id')->pluck('action')->all());
        Http::assertSentCount(1);
    }

    public function test_the_migration_rolls_back_without_changing_the_soap_schema_or_deleting_audit_rows(): void
    {
        $this->enqueue();
        $before = Schema::getColumnListing('erp_jobs');
        $logCount = ActionLog::query()->count();
        (require database_path('migrations/2026_10_12_130000_create_rest_erp_jobs_table.php'))->down();
        $this->assertFalse(Schema::hasTable('rest_erp_jobs'));
        $this->assertFalse(Schema::hasTable('rest_erp_job_actors'));
        $this->assertSame($before, Schema::getColumnListing('erp_jobs'));
        $this->assertSame($logCount, ActionLog::query()->count());
    }

    public function test_invalid_utf8_is_rejected_and_audited_instead_of_throwing_a_serialization_error(): void
    {
        $result = resolve(ItemReclassJournalsService::class)->queue("\xFF", 'test', 1, 'LAMARI');
        $this->assertSame(422, $result['http_status']);
        $job = RestErpJob::query()->sole();
        $this->assertSame('rejected', $job->status);
        $this->assertSame(1, $job->actionLogs()->count());
        $this->assertSame([], $job->parameters);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_http_publication_failure_returns_a_safe_error_and_a_trackable_request_id(): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'basket-batch-update', 'guard_name' => 'web']));
        DB::table('baskets')->insert(['id' => 1, 'code' => 'BKJ1038538', 'user_id' => 42, 'status' => 'new']);
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('password=broker-secret'));

        $response = $this->postJson('/api/basket-batch-update', ['batch' => 'test', 'batch_name' => 'LAMARI']);

        $response->assertStatus(503)->assertJsonPath('error.code', 'ERP_QUEUE_ERROR');
        $job = RestErpJob::query()->sole();
        $response->assertJsonPath('rest_erp_job_id', $job->id);
        $this->assertSame('failed', $job->status);
        $this->assertStringNotContainsString('broker-secret', $response->getContent());
        Http::assertNothingSent();
    }

    public function test_sent_parameters_are_complete_in_storage_api_and_action_logs_without_truncation(): void
    {
        $codes = implode(',', array_fill(0, 1000, 'BKJ1038538'));
        $result = resolve(ItemReclassJournalsService::class)->queue($codes, 'تست', 1000, 'LAMARI');
        $job = RestErpJob::query()->findOrFail($result['data']['rest_erp_job_id']);
        $parameters = ['ArrayCrossReferenceNo' => $codes, 'BatchNo' => 'تست', 'Number' => '1000', 'BatchName' => 'LAMARI'];
        $this->assertSame($parameters, json_decode($job->getRawOriginal('parameters'), true, 512, JSON_THROW_ON_ERROR));
        foreach ($job->actionLogs()->get() as $log) {
            $this->assertSame($parameters, $log->data['snapshot']['parameters']);
            $this->assertArrayNotHasKey('payload_hash', $log->data['snapshot']);
        }
        Auth::user()->givePermissionTo(Permission::create(['name' => 'erp-jobs', 'guard_name' => 'web']));
        $this->getJson('/api/rest-erp-jobs/'.$job->id)->assertOk()->assertJsonPath('data.parameters', $parameters);
        $this->getJson('/api/rest-erp-jobs')->assertOk()->assertJsonPath('data.0.parameters', $parameters);
        Http::fake([self::ERP_URL => Http::response(['Number' => '1000'], 201)]);
        resolve(RestErpJobService::class)->execute($job->id);
        $this->assertSame('success', $job->fresh()->status);
        Http::assertSent(fn (HttpRequest $request) => $request->data() === $parameters);
    }

    public function test_legacy_encrypted_parameters_remain_readable_and_can_still_execute(): void
    {
        $job = $this->enqueue();
        $parameters = $job->parameters;
        DB::table('rest_erp_jobs')->where('id', $job->id)->update(['parameters' => Crypt::encryptString(json_encode($parameters, JSON_THROW_ON_ERROR))]);
        $this->assertSame($parameters, $job->fresh()->parameters);
        Http::fake([self::ERP_URL => Http::response(['Number' => '1'], 201)]);
        resolve(RestErpJobService::class)->execute($job->id);
        $this->assertSame('success', $job->fresh()->status);
        Http::assertSent(fn (HttpRequest $request) => $request->data() === $parameters);
    }
}
