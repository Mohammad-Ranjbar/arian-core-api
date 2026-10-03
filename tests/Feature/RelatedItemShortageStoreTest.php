<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\MainItemShortage;
use App\Models\RelatedItemShortage;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class RelatedItemShortageStoreTest extends TestCase
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
        DB::table('car_models')->insert(['id' => 1]);

        (require database_path('migrations/2026_09_07_143351_create_main_item_shortages_table.php'))->up();
        (require database_path('migrations/2026_09_20_110859_add_routing_link_code_to_main_item_shortages_table.php'))->up();
        (require database_path('migrations/2026_09_12_104714_create_related_item_shortages_table.php'))->up();

        $this->withoutMiddleware([
            Authenticate::class,
            Authorize::class,
            CheckActiveUserMiddleware::class,
        ]);
    }

    public function test_store_adds_an_item_to_a_draft(): void
    {
        $main = $this->createMain();

        $this->postJson('/api/related-item-shortages', $this->payload($main))
            ->assertOk()
            ->assertJsonPath('main_item_shortage_id', $main->id)
            ->assertJsonPath('item_no', 'NEW-CHILD')
            ->assertJsonPath('data', ['qty' => 2]);

        $this->assertDatabaseCount('main_item_shortages', 1);
        $this->assertDatabaseCount('related_item_shortages', 1);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_store_forks_an_active_main_and_preserves_existing_data(): void
    {
        $this->assertForkPreservesData(true, 'LOCATION-1');
    }

    public function test_store_forks_a_draft_when_its_location_changes(): void
    {
        $this->assertForkPreservesData(false, 'LOCATION-2');
    }

    public function test_store_returns_the_original_error_and_rolls_back_the_fork(): void
    {
        $main = $this->createMain(true);
        $main->relatedItems()->create([
            'item_no' => 'OLD-CHILD',
            'description' => 'Existing child',
            'data' => ['qty' => 3],
        ]);

        Event::listen('eloquent.creating: '.RelatedItemShortage::class, function (RelatedItemShortage $item): void {
            if ($item->item_no === 'NEW-CHILD') {
                throw new RuntimeException('Related item creation failed');
            }
        });

        $this->postJson('/api/related-item-shortages', $this->payload($main))
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Related item creation failed']);

        $this->assertDatabaseCount('main_item_shortages', 1);
        $this->assertDatabaseCount('related_item_shortages', 1);
        $this->assertSame(['qty' => 3], $main->relatedItems()->sole()->data);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_store_validates_the_main_before_accessing_it(): void
    {
        $this->postJson('/api/related-item-shortages', [
            'main_item_shortage_id' => 999,
            'item_no' => 'NEW-CHILD',
            'description' => 'New child',
            'location_code' => 'LOCATION-1',
            'data' => ['qty' => 2],
        ])->assertUnprocessable()->assertJsonValidationErrors('main_item_shortage_id');
    }

    public function test_store_allows_a_location_only_change_without_an_item(): void
    {
        $main = $this->createMain();

        $this->postJson('/api/related-item-shortages', [
            'main_item_shortage_id' => $main->id,
            'location_code' => 'LOCATION-2',
            'data' => ['qty' => 2],
        ])->assertOk()->assertExactJson(['message' => 'تغییرات با موفقیت انجام شد']);

        $this->assertDatabaseCount('main_item_shortages', 2);
        $this->assertDatabaseCount('related_item_shortages', 0);
    }

    public function test_store_rejects_the_main_item_and_duplicate_related_items(): void
    {
        $main = $this->createMain();
        $payload = $this->payload($main);
        $payload['item_no'] = $main->item_no;

        $this->postJson('/api/related-item-shortages', $payload)->assertStatus(400);

        $main->relatedItems()->create([
            'item_no' => 'NEW-CHILD',
            'description' => 'Existing child',
            'data' => ['qty' => 1],
        ]);

        $this->postJson('/api/related-item-shortages', $this->payload($main))
            ->assertJsonPath('message', 'ایتم انتخابی در ایتم های وابسته این مورد موجود است');

        $this->assertDatabaseCount('related_item_shortages', 1);
    }

    private function assertForkPreservesData(bool $active, string $location): void
    {
        $main = $this->createMain($active);
        $original = $main->relatedItems()->create([
            'item_no' => 'OLD-CHILD',
            'description' => 'Existing child',
            'data' => ['qty' => 3],
        ]);

        $this->postJson('/api/related-item-shortages', $this->payload($main, $location))
            ->assertOk()
            ->assertJsonPath('item_no', 'NEW-CHILD')
            ->assertJsonPath('data', ['qty' => 2]);

        $fork = MainItemShortage::query()->where('main_item_shortage_id', $main->id)->sole();
        $this->assertSame($location, $fork->location_code);
        $this->assertSame(2, $fork->version);
        $this->assertFalse($fork->is_active);
        $this->assertSame($main->data, $fork->data);
        $this->assertSame(['qty' => 3], $fork->relatedItems()->where('item_no', 'OLD-CHILD')->sole()->data);
        $this->assertSame(['qty' => 3], $original->fresh()->data);
        $this->assertSame('LOCATION-1', $main->fresh()->location_code);
        $this->assertSame($active, $main->fresh()->is_active);
        $this->assertSame(1, $main->relatedItems()->count());
        $this->assertDatabaseCount('main_item_shortages', 2);
        $this->assertDatabaseCount('related_item_shortages', 3);
    }

    private function createMain(bool $active = false): MainItemShortage
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

    private function payload(MainItemShortage $main, string $location = 'LOCATION-1'): array
    {
        return [
            'main_item_shortage_id' => $main->id,
            'item_no' => 'NEW-CHILD',
            'description' => 'New child',
            'location_code' => $location,
            'data' => ['qty' => 2],
        ];
    }
}
