<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\ActionLog;
use App\Models\SpecificShortageItem;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SpecificShortageItemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createTables();
        $this->seedReferenceData();
        $this->actingAs(User::query()->findOrFail(1));

        $this->withoutMiddleware([
            Authenticate::class,
            Authorize::class,
            CheckActiveUserMiddleware::class,
        ]);
    }

    public function test_crud_uses_migration_fields_and_resource_relations(): void
    {
        $storeResponse = $this->postJson('/api/specific-shortage-items', [
            'item_no' => 'ITEM-100',
            'description' => 'Specific shortage item',
            'erp_product_item_id' => 1,
            'car_model_id' => 1,
            'is_active' => true,
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('data.qc_code', '84')
            ->assertJsonPath('data.item_no', 'ITEM-100')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.erpProductItem.reference', 'ERP-100')
            ->assertJsonPath('data.carModel.reference', 'CAR-100')
            ->assertJsonPath('data.actionLogs.0.action', ActionLog::SPECIFIC_SHORTAGE_ITEM_CREATED)
            ->assertJsonPath('data.actionLogs.0.user.id', 1)
            ->assertJsonCount(1, 'data.actionLogs');

        $itemId = $storeResponse->json('data.id');

        $createdLog = ActionLog::query()
            ->where('action', ActionLog::SPECIFIC_SHORTAGE_ITEM_CREATED)
            ->sole();

        $this->assertSame(SpecificShortageItem::class, $createdLog->model_type);
        $this->assertSame((string) $itemId, $createdLog->model_id);
        $this->assertSame(1, $createdLog->user_id);
        $this->assertNull($createdLog->data['before']);
        $this->assertSame('ITEM-100', $createdLog->data['after']['item_no']);
        $this->assertSame('84', $createdLog->data['after']['qc_code']);
        $this->assertEqualsCanonicalizing([
            'id',
            'qc_code',
            'item_no',
            'description',
            'erp_product_item_id',
            'car_model_id',
            'is_active',
            'created_at',
            'updated_at',
        ], array_keys($createdLog->data['after']));

        $this->getJson('/api/specific-shortage-items')
            ->assertOk()
            ->assertJsonPath('data.0.id', $itemId)
            ->assertJsonPath('data.0.actionLogs.0.action', ActionLog::SPECIFIC_SHORTAGE_ITEM_CREATED)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->putJson("/api/specific-shortage-items/{$itemId}", [
            'item_no' => 'ITEM-101',
            'description' => 'Updated description',
            'erp_product_item_id' => 1,
            'car_model_id' => 1,
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.qc_code', '84')
            ->assertJsonPath('data.item_no', 'ITEM-101')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.actionLogs.1.action', ActionLog::SPECIFIC_SHORTAGE_ITEM_DEACTIVATED)
            ->assertJsonCount(2, 'data.actionLogs');

        $deactivatedLog = ActionLog::query()
            ->where('action', ActionLog::SPECIFIC_SHORTAGE_ITEM_DEACTIVATED)
            ->sole();

        $this->assertTrue((bool) $deactivatedLog->data['before']['is_active']);
        $this->assertFalse((bool) $deactivatedLog->data['after']['is_active']);
        $this->assertSame('ITEM-100', $deactivatedLog->data['before']['item_no']);
        $this->assertSame('ITEM-101', $deactivatedLog->data['after']['item_no']);

        $this->putJson("/api/specific-shortage-items/{$itemId}", [
            'item_no' => 'ITEM-101',
            'description' => 'Updated description',
            'erp_product_item_id' => 1,
            'car_model_id' => 1,
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.actionLogs.2.action', ActionLog::SPECIFIC_SHORTAGE_ITEM_ACTIVATED)
            ->assertJsonCount(3, 'data.actionLogs');

        $activatedLog = ActionLog::query()
            ->where('action', ActionLog::SPECIFIC_SHORTAGE_ITEM_ACTIVATED)
            ->sole();

        $this->assertFalse((bool) $activatedLog->data['before']['is_active']);
        $this->assertTrue((bool) $activatedLog->data['after']['is_active']);

        $this->deleteJson("/api/specific-shortage-items/{$itemId}")
            ->assertOk()
            ->assertJsonPath('message', 'item deleted!');

        $this->assertDatabaseCount('specific_shortage_items', 0);

        $deletedLog = ActionLog::query()
            ->where('action', ActionLog::SPECIFIC_SHORTAGE_ITEM_DELETED)
            ->sole();

        $this->assertSame('ITEM-101', $deletedLog->data['before']['item_no']);
        $this->assertNull($deletedLog->data['after']);
        $this->assertDatabaseCount('action_logs', 4);
    }

    public function test_store_validates_required_fields_and_foreign_keys(): void
    {
        $this->postJson('/api/specific-shortage-items', [
            'erp_product_item_id' => 999,
            'car_model_id' => 999,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'item_no',
                'description',
                'erp_product_item_id',
                'car_model_id',
                'is_active',
            ]);

        $this->assertDatabaseCount('specific_shortage_items', 0);
        $this->assertDatabaseCount('action_logs', 0);
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('password')->nullable();
            $table->string('national_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });

        Schema::create('station_user', function (Blueprint $table) {
            $table->foreignId('station_id');
            $table->foreignId('user_id');
            $table->json('data')->nullable();
        });

        Schema::create('erp_product_items', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('tracking_code')->nullable();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('reference');
            $table->timestamps();
        });

        Schema::create('car_model_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id');
        });

        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('model_id');
            $table->string('action');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        $migration = require database_path(
            'migrations/2026_10_04_152056_create_specific_shortage_items_table.php'
        );
        $migration->up();
    }

    private function seedReferenceData(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Test User',
            'is_active' => true,
        ]);

        DB::table('erp_product_items')->insert([
            'id' => 1,
            'reference' => 'ERP-100',
            'title' => 'ERP item',
            'description' => 'ERP description',
        ]);

        DB::table('car_models')->insert([
            'id' => 1,
            'title' => 'Car model',
            'reference' => 'CAR-100',
        ]);
    }
}
