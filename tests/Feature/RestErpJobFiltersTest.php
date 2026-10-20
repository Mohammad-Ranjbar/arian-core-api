<?php

namespace Tests\Feature;

use App\Models\RestErpJob;
use App\Services\Erp\RestApi\RestErpJobService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class RestErpJobFiltersTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        Http::preventStrayRequests();
        Queue::fake();
    }

    private function authorizeListing(): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'erp-jobs', 'guard_name' => 'web']));
    }

    private function job(array $attributes = []): RestErpJob
    {
        $result = resolve(RestErpJobService::class)->enqueue('ExitPrints', ['VIN' => 'VIN-1', 'ItemType' => '2']);
        $job = RestErpJob::query()->findOrFail($result['data']['rest_erp_job_id']);
        $job->forceFill($attributes)->save();

        return $job;
    }

    public function test_filter_options_are_distinct_and_never_expose_service_namespaces(): void
    {
        $this->authorizeListing();
        $this->job(['section' => 'ثبت بارکد', 'service' => 'App\\Services\\Erp\\RestApi\\TrackingsService']);
        $this->job(['section' => 'ثبت بارکد', 'service' => 'Legacy\\TrackingsService']);
        $this->job(['section' => 'چاپ مجوز', 'service' => 'App\\Services\\Erp\\RestApi\\ExitPrintsService']);

        $response = $this->getJson('/api/rest-erp-jobs/filters')->assertOk();

        $this->assertEqualsCanonicalizing([
            ['value' => 'ثبت بارکد', 'label' => 'ثبت بارکد'],
            ['value' => 'چاپ مجوز', 'label' => 'چاپ مجوز'],
        ], $response->json('data.sections'));
        $this->assertSame([
            ['value' => 'ExitPrintsService', 'label' => 'ExitPrintsService'],
            ['value' => 'TrackingsService', 'label' => 'TrackingsService'],
        ], $response->json('data.services'));
        $this->assertSame(['sync', 'parallel', 'unique'], array_column($response->json('data.queues'), 'value'));
        $this->assertSame(['pending', 'in-progress', 'success', 'failed', 'uncertain', 'rejected', 'ignored'], array_column($response->json('data.statuses'), 'value'));
        $this->assertStringNotContainsString('App\\', $response->getContent());
        $this->assertStringNotContainsString('Legacy', $response->getContent());
        Http::assertNothingSent();
    }

    public function test_empty_database_still_exposes_valid_queues_and_statuses(): void
    {
        $this->authorizeListing();

        $this->getJson('/api/rest-erp-jobs/filters')->assertOk()
            ->assertExactJson(['data' => [
                'sections' => [], 'services' => [],
                'queues' => [
                    ['value' => 'sync', 'label' => 'اجرای فوری'],
                    ['value' => 'parallel', 'label' => 'صف پردازش موازی'],
                    ['value' => 'unique', 'label' => 'صف پردازش تک‌به‌تک'],
                ],
                'statuses' => [
                    ['value' => 'pending', 'label' => 'در انتظار پردازش'],
                    ['value' => 'in-progress', 'label' => 'در حال پردازش'],
                    ['value' => 'success', 'label' => 'موفق'],
                    ['value' => 'failed', 'label' => 'ناموفق'],
                    ['value' => 'uncertain', 'label' => 'نتیجه نامشخص'],
                    ['value' => 'rejected', 'label' => 'ردشده در اعتبارسنجی'],
                    ['value' => 'ignored', 'label' => 'نادیده گرفته‌شده'],
                ],
            ]]);
    }

    public function test_filter_options_require_the_listing_permission(): void
    {
        $this->getJson('/api/rest-erp-jobs/filters')->assertForbidden();
    }

    public function test_short_service_filter_handles_duplicate_basenames_and_combines_with_other_filters(): void
    {
        $this->authorizeListing();
        $first = $this->job(['service' => 'App\\Services\\Erp\\RestApi\\TrackingsService', 'section' => 'ثبت بارکد', 'status' => 'failed', 'queue' => 'unique']);
        $second = $this->job(['service' => 'Legacy\\TrackingsService', 'section' => 'ثبت بارکد', 'status' => 'failed', 'queue' => 'unique']);
        $this->job(['section' => 'ثبت بارکد', 'status' => 'failed', 'queue' => 'unique']);
        $this->job(['service' => 'Legacy\\TrackingsService', 'section' => 'ثبت بارکد', 'status' => 'success', 'queue' => 'unique']);
        $this->job(['service' => 'Legacy\\TrackingsService', 'section' => 'ثبت بارکد', 'status' => 'failed', 'queue' => 'parallel']);
        $query = http_build_query(['service' => 'TrackingsService', 'section' => 'ثبت بارکد', 'status' => 'failed', 'queue' => 'unique']);

        $response = $this->getJson('/api/rest-erp-jobs?'.$query)->assertOk();

        $this->assertSame([$second->id, $first->id], array_column($response->json('data'), 'id'));
        $this->assertSame(2, $response->json('meta.total'));
    }

    public static function queues(): array
    {
        return [['sync'], ['parallel'], ['unique']];
    }

    #[DataProvider('queues')]
    public function test_queue_filter_distinguishes_sync_from_actual_queues(string $mode): void
    {
        $this->authorizeListing();
        $ids = [];
        foreach (['sync', 'parallel', 'unique'] as $queue) {
            $ids[$queue] = $this->job([
                'execution_mode' => $queue === 'sync' ? 'sync' : 'queue',
                'queue' => $queue === 'sync' ? null : $queue,
            ])->id;
        }

        $this->getJson('/api/rest-erp-jobs?queue='.$mode)->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ids[$mode]);
    }

    public function test_invalid_service_or_queue_filters_are_rejected(): void
    {
        $this->authorizeListing();

        $this->getJson('/api/rest-erp-jobs?queue=invalid')->assertUnprocessable()->assertJsonValidationErrors('queue');
        $this->getJson('/api/rest-erp-jobs?service=UnknownService')->assertUnprocessable()->assertJsonValidationErrors('service');
    }
}
