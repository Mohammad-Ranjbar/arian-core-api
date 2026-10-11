<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\ActionLog;
use App\Models\MainItemShortage;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesShortageAuditTables;
use Tests\TestCase;

class MainItemShortageDeleteTest extends TestCase
{
    use CreatesShortageAuditTables;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createShortageAuditTablesAndAuthenticate();

        Schema::create('car_models', function (Blueprint $table): void {
            $table->id();
        });
        (require database_path('migrations/2026_09_07_143351_create_main_item_shortages_table.php'))->up();
        (require database_path('migrations/2026_09_20_110859_add_routing_link_code_to_main_item_shortages_table.php'))->up();
        (require database_path('migrations/2026_09_12_104714_create_related_item_shortages_table.php'))->up();

        Schema::create('register_shortages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('main_item_shortage_id')->nullable();
            $table->timestamps();
        });

        DB::table('car_models')->insert(['id' => 1]);

        $this->withoutMiddleware([
            Authenticate::class,
            Authorize::class,
            CheckActiveUserMiddleware::class,
        ]);
    }

    public function test_cannot_delete_a_main_item_used_by_a_registered_shortage(): void
    {
        $mainItemShortage = $this->createMainItemShortage();
        DB::table('register_shortages')->insert([
            'main_item_shortage_id' => $mainItemShortage->getKey(),
        ]);

        $this->deleteJson('/api/main-item-shortages/'.$mainItemShortage->getKey())
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'این مورد در کسری استفاده شده و امکان حذف آن وجود ندارد.',
            ]);

        $this->assertDatabaseHas('main_item_shortages', [
            'id' => $mainItemShortage->getKey(),
        ]);
    }

    public function test_store_audits_a_new_main_item(): void
    {
        $this->postJson('/api/main-item-shortages', [
            'location_code' => 'LOCATION-1',
            'car_model_id' => 1,
            'car_model_reference' => 'CAR-1',
            'item_no' => 'MAIN-ITEM',
            'description' => 'Main item',
            'data' => ['routing_link_code' => 'ROUTING-1'],
        ])->assertCreated()
            ->assertJsonPath('item_no', 'MAIN-ITEM');

        $mainItemShortage = MainItemShortage::query()->sole();
        $log = ActionLog::query()
            ->where('action', ActionLog::MAIN_ITEM_SHORTAGE_CREATED)
            ->where('model_id', (string) $mainItemShortage->getKey())
            ->sole();

        $this->assertSame(1, $log->user_id);
        $this->assertNull($log->data['before']);
        $this->assertSame('MAIN-ITEM', $log->data['after']['item_no']);
    }

    public function test_can_delete_an_unused_main_item(): void
    {
        $mainItemShortage = $this->createMainItemShortage();

        $this->deleteJson('/api/main-item-shortages/'.$mainItemShortage->getKey())
            ->assertOk()
            ->assertExactJson(['message' => 'ایتم با موفقیت حذف شد']);

        $this->assertDatabaseMissing('main_item_shortages', [
            'id' => $mainItemShortage->getKey(),
        ]);

        $log = ActionLog::query()
            ->where('action', ActionLog::MAIN_ITEM_SHORTAGE_DELETED)
            ->where('model_id', (string) $mainItemShortage->getKey())
            ->sole();
        $this->assertSame('MAIN-ITEM', $log->data['before']['item_no']);
        $this->assertNull($log->data['after']);
    }

    public function test_activate_audits_both_the_previous_and_new_active_versions(): void
    {
        $active = $this->createMainItemShortage(true);
        $draft = $this->createMainItemShortage(false);

        $this->postJson('/api/main-item-shortages/'.$draft->getKey().'/activate')
            ->assertOk()
            ->assertJsonPath('is_active', true);

        $this->assertFalse($active->fresh()->is_active);
        $this->assertTrue($draft->fresh()->is_active);

        $logs = ActionLog::query()
            ->where('action', ActionLog::MAIN_ITEM_SHORTAGE_UPDATED)
            ->whereIn('model_id', [(string) $active->getKey(), (string) $draft->getKey()])
            ->get();

        $this->assertCount(2, $logs);
        $this->assertEqualsCanonicalizing(
            [$active->getKey(), $draft->getKey()],
            $logs->pluck('model_id')->map(fn (string $id): int => (int) $id)->all(),
        );
    }

    private function createMainItemShortage(bool $active = false): MainItemShortage
    {
        return MainItemShortage::query()->create([
            'location_code' => 'LOCATION-1',
            'car_model_id' => 1,
            'car_model_reference' => 'CAR-1',
            'item_no' => 'MAIN-ITEM',
            'description' => 'Main item',
            'is_active' => $active,
            'data' => ['routing_link_code' => 'ROUTING-1'],
        ]);
    }
}
