<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckActiveUserMiddleware;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MainItemShortageFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('car_models', function (Blueprint $table): void {
            $table->id();
        });
        DB::table('car_models')->insert([['id' => 1], ['id' => 2]]);

        (require database_path('migrations/2026_09_07_143351_create_main_item_shortages_table.php'))->up();
        (require database_path('migrations/2026_09_20_110859_add_routing_link_code_to_main_item_shortages_table.php'))->up();
        (require database_path('migrations/2026_09_12_104714_create_related_item_shortages_table.php'))->up();

        foreach ([[1, 1], [2, 1], [1, 0], [2, 0]] as $index => [$carModelId, $isActive]) {
            DB::table('main_item_shortages')->insert([
                'id' => $index + 1,
                'location_code' => 'LOCATION-1',
                'car_model_id' => $carModelId,
                'car_model_reference' => "CAR-{$carModelId}",
                'item_no' => "ITEM-{$index}",
                'description' => 'Main item',
                'is_active' => $isActive,
            ]);
        }

        $this->withoutMiddleware([
            Authenticate::class,
            Authorize::class,
            CheckActiveUserMiddleware::class,
        ]);
    }

    public function test_index_combines_active_and_car_model_filters(): void
    {
        $this->getJson('/api/main-item-shortages?is_active=1&car_model_id=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.car_model_id', 1)
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.related_items', [])
            ->assertJsonPath('meta.total', 1);
    }

    public function test_index_filters_by_another_car_model_without_active_filter(): void
    {
        $this->getJson('/api/main-item-shortages?car_model_id=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 4)
            ->assertJsonPath('data.0.car_model_id', 2)
            ->assertJsonPath('data.1.id', 2)
            ->assertJsonPath('data.1.car_model_id', 2)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_index_returns_no_results_for_an_unmatched_car_model(): void
    {
        $this->getJson('/api/main-item-shortages?is_active=1&car_model_id=999')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }
}
