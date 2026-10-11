<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\ActionLog;
use App\Models\MainItemShortage;
use App\Models\RelatedItemShortage;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Concerns\CreatesShortageAuditTables;
use Tests\TestCase;

class RelatedItemShortageDeleteTest extends TestCase
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

    public function test_delete_removes_an_item_directly_from_a_draft(): void
    {
        $main = $this->createMain();
        $removed = $this->createRelated($main, 'REMOVED-CHILD');
        $retained = $this->createRelated($main, 'RETAINED-CHILD');

        $this->deleteJson('/api/related-item-shortages/'.$removed->id)
            ->assertOk()
            ->assertExactJson(['message' => 'item deleted!']);

        $this->assertDatabaseMissing('related_item_shortages', ['id' => $removed->id]);
        $this->assertSame($retained->data, $retained->fresh()->data);
        $this->assertDatabaseCount('main_item_shortages', 1);
        $this->assertDatabaseCount('related_item_shortages', 1);
        $this->assertSame(0, DB::transactionLevel());

        $log = ActionLog::query()
            ->where('action', ActionLog::RELATED_ITEM_SHORTAGE_DELETED)
            ->where('model_id', (string) $removed->getKey())
            ->sole();
        $this->assertSame('REMOVED-CHILD', $log->data['before']['item_no']);
        $this->assertNull($log->data['after']);
    }

    public function test_delete_forks_an_active_main_without_the_selected_item(): void
    {
        $main = $this->createMain(true);
        $removed = $this->createRelated($main, 'REMOVED-CHILD');
        $retained = $this->createRelated($main, 'RETAINED-CHILD');

        $this->deleteJson('/api/related-item-shortages/'.$removed->id)->assertOk();

        $fork = $main->versions()->sole();
        $this->assertFalse($fork->is_active);
        $this->assertSame(2, $fork->version);
        $this->assertSame($main->location_code, $fork->location_code);
        $this->assertSame($main->data, $fork->data);
        $copied = $fork->relatedItems()->sole();
        $this->assertSame($retained->item_no, $copied->item_no);
        $this->assertSame($retained->data, $copied->data);
        $this->assertTrue($main->fresh()->is_active);
        $this->assertNotNull($removed->fresh());
        $this->assertNotNull($retained->fresh());
        $this->assertSame(2, $main->relatedItems()->count());
        $this->assertDatabaseCount('main_item_shortages', 2);
        $this->assertDatabaseCount('related_item_shortages', 3);
        $this->assertSame(0, DB::transactionLevel());

        $log = ActionLog::query()
            ->where('action', ActionLog::RELATED_ITEM_SHORTAGE_DELETED)
            ->where('model_id', (string) $removed->getKey())
            ->sole();
        $this->assertSame($fork->getKey(), $log->data['forked_main_item_shortage_id']);
        $this->assertDatabaseHas('related_item_shortages', ['id' => $removed->getKey()]);
    }

    public function test_delete_the_last_item_of_an_active_main_creates_an_empty_draft(): void
    {
        $main = $this->createMain(true);
        $item = $this->createRelated($main, 'ONLY-CHILD');

        $this->deleteJson('/api/related-item-shortages/'.$item->id)->assertOk();

        $fork = $main->versions()->sole();
        $this->assertFalse($fork->is_active);
        $this->assertSame(0, $fork->relatedItems()->count());
        $this->assertNotNull($item->fresh());
        $this->assertDatabaseCount('main_item_shortages', 2);
        $this->assertDatabaseCount('related_item_shortages', 1);
    }

    public function test_delete_rolls_back_the_fork_if_copying_a_remaining_item_fails(): void
    {
        $main = $this->createMain(true);
        $removed = $this->createRelated($main, 'REMOVED-CHILD');
        $this->createRelated($main, 'RETAINED-CHILD');
        $this->createRelated($main, 'FAILED-CHILD');

        Event::listen('eloquent.creating: '.RelatedItemShortage::class, function (RelatedItemShortage $item): void {
            if ($item->item_no === 'FAILED-CHILD') {
                throw new RuntimeException('Related item copy failed');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->deleteJson('/api/related-item-shortages/'.$removed->id);
            $this->fail('Expected the copy failure to propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Related item copy failed', $exception->getMessage());
        }

        $this->assertDatabaseCount('main_item_shortages', 1);
        $this->assertDatabaseCount('related_item_shortages', 3);
        $this->assertDatabaseMissing('action_logs', [
            'action' => ActionLog::MAIN_ITEM_SHORTAGE_FORKED,
        ]);
        $this->assertSame(0, $main->versions()->count());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_delete_returns_not_found_for_a_missing_item(): void
    {
        $this->deleteJson('/api/related-item-shortages/999')->assertNotFound();
        $this->assertDatabaseCount('main_item_shortages', 0);
        $this->assertDatabaseCount('related_item_shortages', 0);
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

    private function createRelated(MainItemShortage $main, string $itemNo): RelatedItemShortage
    {
        return $main->relatedItems()->create([
            'item_no' => $itemNo,
            'description' => $itemNo,
            'data' => ['item_no' => $itemNo, 'qty' => 3],
        ]);
    }
}
