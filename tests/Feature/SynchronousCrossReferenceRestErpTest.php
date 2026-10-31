<?php

namespace Tests\Feature;

use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Models\SynchronousItem;
use App\Services\Erp\ErpCrossReference;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class SynchronousCrossReferenceRestErpTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        $this->createSynchronousTables();
        config()->set('erp.auth', ['test-user', 'test-password', 'ntlm']);
        Http::preventStrayRequests();
        Queue::fake();
        Carbon::setTestNow('2026-10-26 10:30:00');

        $references = Mockery::mock(ErpCrossReference::class);
        $references->shouldReceive('get')->with('PART-1')->andReturn(['SX5G-2804-0056']);
        $this->app->instance(ErpCrossReference::class, $references);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function modes(): array
    {
        return [['sync'], ['parallel'], ['unique']];
    }

    #[DataProvider('modes')]
    public function test_last_item_dispatches_the_aggregated_rest_payload_in_the_selected_mode(string $mode): void
    {
        config()->set('rest_erp.endpoints.CreateSynchroens.mode', $mode);
        Http::fake(['*' => Http::response(['Doc' => 'P-10-00932'], 201)]);

        $response = $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056']);

        $response->assertStatus($mode === 'sync' ? 200 : 202);
        $job = RestErpJob::query()->sole();
        $this->assertSame('CreateSynchroens', $job->endpoint);
        $this->assertSame(SynchronousItem::class, $job->model_type);
        $this->assertSame('2', $job->model_id);
        $this->assertSame(42, $job->user_id);
        $this->assertSame('ارسال سنکرون قطعات به ERP', $job->section);
        $this->assertSame([
            'CrossReference' => 'REF-OLD,SX5G-2804-0056',
            'Doc' => 'P-10-00932',
            'LocationCode' => 'LMRLP0100',
            'NewLocationCode' => 'LMRIL0011',
            'VariantCode' => '213,214',
            'Number' => '2',
            'BatchNo' => '2026-10-26 10:30:00',
        ], $job->parameters);
        $this->assertDatabaseHas('synchronous_items', ['id' => 2, 'checked' => true, 'cross_reference' => 'SX5G-2804-0056']);
        $this->assertDatabaseCount('erp_jobs', 0);
        if ($mode === 'sync') {
            $response->assertJsonPath('rest_erp_job_id', $job->id)->assertJsonPath('request_id', $job->uuid);
            $this->assertSame('success', $job->status);
            Http::assertSentCount(1);
            Queue::assertNothingPushed();
        } else {
            $response->assertJsonPath('rest_erp_job_id', $job->id)->assertJsonPath('request_id', $job->uuid);
            $this->assertSame('pending', $job->status);
            Queue::assertPushedOn($mode, SendToRestErpJob::class);
            Http::assertNothingSent();
        }
    }

    public function test_queued_delivery_waits_for_commit(): void
    {
        DB::beginTransaction();
        $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056'])->assertAccepted();
        Queue::assertNothingPushed();
        DB::commit();

        Queue::assertPushedOn('unique', SendToRestErpJob::class);
    }

    #[DataProvider('modes')]
    public function test_a_stale_route_bound_item_cannot_dispatch_the_completed_group_again(string $mode): void
    {
        config()->set('rest_erp.endpoints.CreateSynchroens.mode', $mode);
        Http::fake(['*' => Http::response([], 200)]);
        $staleItem = SynchronousItem::query()->findOrFail(2);

        $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056'])
            ->assertStatus($mode === 'sync' ? 200 : 202);
        // A concurrent request may have resolved its route model before the first request committed.
        Route::bind('synchronousItem', fn () => $staleItem);
        $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056'])
            ->assertUnprocessable()->assertJsonPath('message', 'سنکرون قبلا ارسال شده است');

        $this->assertDatabaseCount('rest_erp_jobs', 1);
        $this->assertSame($mode === 'sync' ? 1 : 0, RestErpJob::query()->sole()->tries);
        if ($mode === 'sync') {
            Http::assertSentCount(1);
            Queue::assertNothingPushed();
        } else {
            Http::assertNothingSent();
            Queue::assertPushed(SendToRestErpJob::class, 1);
        }
    }

    #[DataProvider('modes')]
    public function test_confirming_two_remaining_items_registers_only_one_group_request(string $mode): void
    {
        config()->set('rest_erp.endpoints.CreateSynchroens.mode', $mode);
        DB::table('synchronous_items')->where('id', 1)->update(['checked' => false]);
        $staleItems = SynchronousItem::query()->where('synchronous_id', 1)->get()->keyBy('id');
        Route::bind('synchronousItem', fn ($id) => $staleItems->get($id));
        Http::fake(['*' => Http::response([], 200)]);

        $this->putJson('/api/synchronous-items/1', ['cross_reference' => 'SX5G-2804-0056'])
            ->assertOk()->assertContent('1');
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056'])
            ->assertStatus($mode === 'sync' ? 200 : 202);
        foreach ([1, 2] as $id) {
            $this->putJson('/api/synchronous-items/'.$id, ['cross_reference' => 'SX5G-2804-0056'])
                ->assertUnprocessable();
        }

        $job = RestErpJob::query()->sole();
        $this->assertSame('2', $job->parameters['Number']);
        $this->assertSame('SX5G-2804-0056,SX5G-2804-0056', $job->parameters['CrossReference']);
        $this->assertSame('213,214', $job->parameters['VariantCode']);
        if ($mode === 'sync') {
            Http::assertSentCount(1);
            Queue::assertNothingPushed();
        } else {
            Http::assertNothingSent();
            Queue::assertPushed(SendToRestErpJob::class, 1);
        }
    }

    public function test_an_incomplete_group_does_not_register_a_job(): void
    {
        DB::table('synchronous_items')->where('id', 1)->update(['checked' => false]);

        $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056'])->assertOk()->assertContent('1');

        $this->assertDatabaseHas('synchronous_items', ['id' => 2, 'checked' => true]);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_an_invalid_reference_does_not_change_the_item(): void
    {
        $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'INVALID'])
            ->assertUnprocessable()->assertJsonPath('message', 'کراس رفرنس وارد شده نامعتبر است');

        $this->assertDatabaseHas('synchronous_items', ['id' => 2, 'checked' => false, 'cross_reference' => null]);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
    }

    public function test_an_already_checked_item_cannot_send_another_job(): void
    {
        $this->putJson('/api/synchronous-items/1', ['cross_reference' => 'SX5G-2804-0056'])
            ->assertUnprocessable()->assertJsonPath('message', 'سنکرون قبلا ارسال شده است');

        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
    }

    public function test_guests_cannot_update_items_or_register_jobs(): void
    {
        Auth::forgetGuards();

        $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056'])->assertUnauthorized();

        $this->assertDatabaseHas('synchronous_items', ['id' => 2, 'checked' => false]);
        $this->assertDatabaseCount('rest_erp_jobs', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_sync_failure_returns_the_erp_error_and_retains_the_job_for_retry(): void
    {
        config()->set('rest_erp.endpoints.CreateSynchroens.mode', 'sync');
        Http::fake(['*' => Http::response(['error' => [
            'code' => 'Application_DialogException', 'message' => 'Insufficient inventory.',
        ]], 400)]);

        $response = $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056'])
            ->assertUnprocessable()->assertJsonPath('error.code', 'Application_DialogException');

        $job = RestErpJob::query()->sole();
        $response->assertJsonPath('rest_erp_job_id', $job->id);
        $this->assertSame('failed', $job->status);
        $this->assertStringContainsString('Insufficient inventory.', $job->description);
        $this->assertDatabaseHas('synchronous_items', ['id' => 2, 'checked' => true]);
        Http::assertSentCount(1);

        $this->putJson('/api/synchronous-items/2', ['cross_reference' => 'SX5G-2804-0056'])->assertUnprocessable();
        $this->assertDatabaseCount('rest_erp_jobs', 1);
        $this->assertSame(1, $job->fresh()->tries);
        Http::assertSentCount(1);
    }

    private function createSynchronousTables(): void
    {
        Schema::create('production_lines', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('central_location');
            $table->string('location');
            $table->softDeletes();
        });
        Schema::create('synchronous_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_id');
        });
        Schema::create('synchronous', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('synchronous_type_id');
            $table->unsignedInteger('counter');
        });
        Schema::create('erp_product_items', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
        });
        Schema::create('synchronous_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('synchronous_id');
            $table->unsignedBigInteger('erp_product_item_id');
            $table->string('vin');
            $table->string('cross_reference')->nullable();
            $table->boolean('checked')->default(false);
            $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->unsignedBigInteger('car_model_id')->nullable();
            $table->unsignedBigInteger('make_code_id')->nullable();
            $table->string('color_code');
            $table->json('data')->nullable();
            $table->softDeletes();
        });
        Schema::create('car_models', fn (Blueprint $table) => $table->id());
        Schema::create('nav_colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_model_id');
            $table->string('color');
            $table->string('code');
        });

        DB::table('production_lines')->insert(['id' => 1, 'prefix' => 'P', 'central_location' => 'LMRLP0100', 'location' => 'LMRIL0011']);
        DB::table('synchronous_types')->insert(['id' => 10, 'production_line_id' => 1]);
        DB::table('synchronous')->insert([
            ['id' => 1, 'synchronous_type_id' => 10, 'counter' => 932],
            ['id' => 2, 'synchronous_type_id' => 10, 'counter' => 933],
        ]);
        DB::table('erp_product_items')->insert(['id' => 1, 'reference' => 'PART-1']);
        DB::table('synchronous_items')->insert([
            ['id' => 1, 'synchronous_id' => 1, 'erp_product_item_id' => 1, 'vin' => 'VIN-1', 'cross_reference' => 'REF-OLD', 'checked' => true],
            ['id' => 2, 'synchronous_id' => 1, 'erp_product_item_id' => 1, 'vin' => 'VIN-2', 'cross_reference' => null, 'checked' => false],
            ['id' => 3, 'synchronous_id' => 2, 'erp_product_item_id' => 1, 'vin' => 'VIN-3', 'cross_reference' => null, 'checked' => false],
        ]);
        DB::table('vehicles')->insert([
            ['vin' => 'VIN-1', 'car_model_id' => 1, 'color_code' => '213', 'data' => json_encode(['ColorCode' => '213'])],
            ['vin' => 'VIN-2', 'car_model_id' => 2, 'color_code' => 'WHITE', 'data' => null],
        ]);
        DB::table('nav_colors')->insert([
            ['car_model_id' => 1, 'color' => 'WHITE', 'code' => 'WRONG'],
            ['car_model_id' => 2, 'color' => 'WHITE', 'code' => '214'],
        ]);
    }
}
