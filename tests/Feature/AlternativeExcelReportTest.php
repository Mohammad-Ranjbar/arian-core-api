<?php

namespace Tests\Feature;

use App\Events\JobProcessEvent;
use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Jobs\ExportJob;
use App\Models\Alternative;
use App\Models\Job;
use App\Models\User;
use App\Services\AlternativeFilters;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AlternativeExcelReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('custom_jobs', function (Blueprint $t) {
            $t->uuid();
            $t->string('type');
            $t->integer('user_id');
            $t->json('data')->nullable();
            $t->json('result')->nullable();
            $t->string('status')->default('pending');
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('erp_product_items', function (Blueprint $t) {
            $t->id();
            $t->string('title');
        });
        Schema::create('alternative_items', function (Blueprint $t) {
            $t->id();
            $t->integer('erp_product_item_id')->nullable();
            $t->decimal('packing_qty');
        });
        Schema::create('alternative_variants', function (Blueprint $t) {
            $t->id();
            $t->integer('alternative_id');
            $t->string('variant_code');
            $t->decimal('quantity');
        });
        Schema::create('alternatives', function (Blueprint $t) {
            $t->id();
            $t->integer('alternative_item_id')->nullable();
            $t->integer('user_id')->nullable();
            $t->string('item_no');
            $t->string('document_number')->nullable();
            $t->string('production_order_number');
            $t->integer('counter');
            $t->string('status');
            $t->timestamp('send_at')->nullable();
            $t->timestamp('failed_at')->nullable();
            $t->timestamp('success_at')->nullable();
            $t->timestamps();
        });
        DB::table('users')->insert(['id' => 42, 'name' => 'کاربر']);
        DB::table('erp_product_items')->insert(['id' => 1, 'title' => 'قطعه']);
        DB::table('alternative_items')->insert(['id' => 1, 'erp_product_item_id' => 1, 'packing_qty' => 2]);
        DB::table('alternatives')->insert([
            ['id' => 81, 'alternative_item_id' => 1, 'user_id' => 42, 'item_no' => '000123', 'document_number' => '=unsafe', 'production_order_number' => 'PO-1', 'counter' => 2, 'status' => 'success', 'success_at' => '2026-03-21 12:30:00', 'created_at' => '2026-03-21 12:30:00'],
            ['id' => 82, 'alternative_item_id' => 1, 'user_id' => 42, 'item_no' => 'OTHER', 'document_number' => null, 'production_order_number' => 'PO-2', 'counter' => 1, 'status' => 'create', 'success_at' => null, 'created_at' => '2026-04-01 10:00:00'],
        ]);
        DB::table('alternative_variants')->insert(['alternative_id' => 81, 'variant_code' => 'V01', 'quantity' => 2]);
        $this->withoutMiddleware(CheckActiveUserMiddleware::class);
        Event::fake([JobProcessEvent::class]);
        Storage::fake('local');
    }

    public function test_non_admin_cannot_start_report(): void
    {
        $this->asUser(42, false);
        $this->getJson('/api/alternatives/report')->assertForbidden();
        $this->assertDatabaseCount('custom_jobs', 0);
    }

    public function test_report_filters_are_persisted_and_job_dispatched(): void
    {
        Queue::fake();
        $this->asUser(42, true);
        $response = $this->getJson('/api/alternatives/report?status=success&item_no=000&created_at_from=2026-03-01&created_at_to=2026-03-31')->assertStatus(202);
        $job = Job::query()->findOrFail($response->json('data.uuid'));
        $this->assertSame('success', $job->getAttribute('data')['status']);
        $this->assertSame('000', $job->getAttribute('data')['item_no']);
        Queue::assertPushed(ExportJob::class, fn ($queued) => $queued->uuid === $job->uuid && $queued->queue === 'exports');
        $this->assertSame([81], AlternativeFilters::apply(Alternative::query(), $job->getAttribute('data'))->pluck('id')->all());
    }

    public function test_job_produces_private_spreadsheet_and_only_owner_admin_can_download(): void
    {
        $job = Job::query()->create(['type' => 'export-alternatives', 'user_id' => 42, 'data' => ['item_no' => '000']]);
        (new ExportJob($job->uuid))->handle();
        $job->refresh();
        $this->assertSame('finished', $job->status);
        $path = 'alternative-reports/'.$job->uuid.'.xlsx';
        Storage::disk('local')->assertExists($path);
        $sheet = IOFactory::load(Storage::disk('local')->path($path))->getActiveSheet();
        $this->assertTrue($sheet->getRightToLeft());
        $this->assertSame('000123', $sheet->getCell('A2')->getValue());
        $this->assertSame('=unsafe', $sheet->getCell('C2')->getValue());
        $this->assertSame('s', $sheet->getCell('C2')->getDataType());
        $this->assertStringStartsWith('1405/01/01', $sheet->getCell('J2')->getValue());
        $this->assertSame(2, $sheet->getHighestDataRow());
        $this->assertNotContains('شناسه', $sheet->rangeToArray('A1:M1')[0]);
        $this->asUser(42, true);
        $this->getJson('/api/alternatives/report/'.$job->uuid)->assertOk()->assertJsonPath('status', 'finished');
        $this->get('/api/alternatives/report/'.$job->uuid.'/download')->assertOk();
        $this->asUser(43, true);
        $this->getJson('/api/alternatives/report/'.$job->uuid)->assertNotFound();
        $this->get('/api/alternatives/report/'.$job->uuid.'/download')->assertNotFound();
        $this->asUser(42, false);
        $this->get('/api/alternatives/report/'.$job->uuid.'/download')->assertForbidden();
    }

    public function test_status_filters_use_the_same_packing_and_timestamp_conditions_as_the_list(): void
    {
        DB::table('alternatives')->insert([
            ['id' => 83, 'alternative_item_id' => 1, 'user_id' => 42, 'item_no' => 'WAIT', 'document_number' => null, 'production_order_number' => 'PO-3', 'counter' => 2, 'status' => 'send', 'success_at' => null, 'failed_at' => null, 'created_at' => '2026-03-22 10:00:00'],
            ['id' => 84, 'alternative_item_id' => 1, 'user_id' => 42, 'item_no' => 'FAIL', 'document_number' => null, 'production_order_number' => 'PO-4', 'counter' => 2, 'status' => 'failed', 'success_at' => null, 'failed_at' => '2026-03-23 10:00:00', 'created_at' => '2026-03-23 10:00:00'],
        ]);
        $ids = fn (array $filters) => AlternativeFilters::apply(Alternative::query(), $filters)->orderBy('id')->pluck('id')->all();
        $this->assertSame([81], $ids(['status' => 'success']));
        $this->assertSame([83, 84], $ids(['status' => 'send']));
        $this->assertSame([84], $ids(['status' => 'failed']));
        $this->assertSame([83], $ids(['created_at_from' => '2026-03-22 10:00:00', 'created_at_to' => '2026-03-22 10:00:00']));
    }

    private function asUser(int $id, bool $admin): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $user->shouldReceive('hasRole')->with('super-admin')->andReturn($admin);
        $this->actingAs($user, 'sanctum');
    }
}
