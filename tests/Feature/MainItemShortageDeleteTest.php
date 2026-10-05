<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\MainItemShortage;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MainItemShortageDeleteTest extends TestCase
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
        (require database_path('migrations/2026_09_07_143351_create_main_item_shortages_table.php'))->up();
        (require database_path('migrations/2026_09_20_110859_add_routing_link_code_to_main_item_shortages_table.php'))->up();

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

    public function test_can_delete_an_unused_main_item(): void
    {
        $mainItemShortage = $this->createMainItemShortage();

        $this->deleteJson('/api/main-item-shortages/'.$mainItemShortage->getKey())
            ->assertOk()
            ->assertExactJson(['message' => 'item deleted!']);

        $this->assertDatabaseMissing('main_item_shortages', [
            'id' => $mainItemShortage->getKey(),
        ]);
    }

    private function createMainItemShortage(): MainItemShortage
    {
        return MainItemShortage::query()->create([
            'location_code' => 'LOCATION-1',
            'car_model_id' => 1,
            'car_model_reference' => 'CAR-1',
            'item_no' => 'MAIN-ITEM',
            'description' => 'Main item',
            'data' => ['routing_link_code' => 'ROUTING-1'],
        ]);
    }
}
