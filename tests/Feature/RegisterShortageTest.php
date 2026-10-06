<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\RegisterShortage;
use App\Models\User;
use App\Services\Erp\ErpGetShortageBOM;
use App\Services\Erp\ErpGetWorkCenterFromRoutingLink;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegisterShortageTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        $this->createTables();
        $this->seedReferenceData();

        $this->user = User::query()->findOrFail(1);
        $this->actingAs($this->user);
        $this->withoutMiddleware([
            Authenticate::class,
            Authorize::class,
            CheckActiveUserMiddleware::class,
        ]);
    }

    public function test_store_derives_vehicle_and_user_fields(): void
    {
        $this->mock(ErpGetShortageBOM::class)
            ->shouldReceive('get')->once()->with('CAR-1')->andReturn([]);

        $response = $this->postJson('/api/register-shortages', [
            'vin' => 'VIN-1',
            'car_model_id' => 999,
            'make_code_id' => 999,
            'item_no' => 'ITEM-1',
            'item_description' => 'Main item',
            'type' => RegisterShortage::TYPE_SINGLE,
            'qty' => 2,
            'work_center' => 'WC-10',
            'status' => RegisterShortage::STATUS_RESOLVED,
            'user_id' => 999,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.car_model_id', 1)
            ->assertJsonPath('data.make_code_id', 1)
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.status', 'دارای کسری');

        $this->assertDatabaseHas('register_shortages', [
            'vin' => 'VIN-1',
            'item_no' => 'ITEM-1',
            'car_model_id' => 1,
            'make_code_id' => 1,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_main_type_requires_routing_link_and_main_item(): void
    {
        $this->postJson('/api/register-shortages', [
            'vin' => 'VIN-1',
            'item_no' => 'ITEM-1',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 1,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.routing_link_code.0', 'برای کسری اصلی، وارد کردن کد مسیر الزامی است.')
            ->assertJsonPath('errors.main_item_shortage_id.0', 'برای کسری اصلی، انتخاب آیتم اصلی الزامی است.');
    }

    public function test_store_rejects_main_item_from_another_car_model(): void
    {
        $mainItemId = $this->createMainItemShortage(2, 'ITEM-1', 'OTHER-CHILD');

        $this->postJson('/api/register-shortages', [
            'vin' => 'VIN-1',
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'ITEM-1',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 1,
            'routing_link_code' => 'LINK-1',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.main_item_shortage_id.0', 'آیتم اصلی انتخاب‌شده باید فعال و متعلق به مدل خودروی انتخاب‌شده باشد.');

        $this->assertDatabaseCount('register_shortages', 0);
    }

    public function test_store_reports_unknown_vin_as_validation_error(): void
    {
        $this->postJson('/api/register-shortages', [
            'vin' => 'UNKNOWN',
            'item_no' => 'ITEM-1',
            'type' => RegisterShortage::TYPE_SINGLE,
            'qty' => 1,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.vin.0', 'خودرویی با این شماره شاسی یافت نشد.');
    }

    public function test_store_rejects_main_item_with_different_item_number(): void
    {
        $mainItemId = $this->createMainItemShortage(1, 'ITEM-2', 'RELATED-2');

        $this->postJson('/api/register-shortages', [
            'vin' => 'VIN-1',
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'ITEM-1',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 1,
            'routing_link_code' => 'LINK-1',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.main_item_shortage_id.0', 'آیتم اصلی انتخاب‌شده باید فعال و متعلق به مدل خودرو باشد و شماره قطعه آن با شماره قطعه ثبت‌شده یکسان باشد.');

        $this->assertDatabaseCount('register_shortages', 0);
    }

    public function test_store_uses_selected_main_item_when_item_numbers_match_across_models(): void
    {
        $this->createMainItemShortage(2, 'ITEM-1', 'OTHER-CHILD');
        $mainItemId = $this->createMainItemShortage(1, 'ITEM-1', 'RIGHT-CHILD');

        $this->mock(ErpGetWorkCenterFromRoutingLink::class)
            ->shouldReceive('get')
            ->once()
            ->with('CAR-1')
            ->andReturn([['routing_link_code' => 'LINK-1', 'work_center' => 'WC-1']]);

        $this->postJson('/api/register-shortages', [
            'vin' => 'VIN-1',
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'ITEM-1',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 1,
            'routing_link_code' => 'LINK-1',
        ])->assertCreated();

        $this->assertDatabaseHas('register_shortages', [
            'vin' => 'VIN-1',
            'item_no' => 'ITEM-1',
            'main_item_shortage_id' => $mainItemId,
            'car_model_id' => 1,
        ]);
        $this->assertDatabaseHas('register_shortages', [
            'vin' => 'VIN-1',
            'item_no' => 'RIGHT-CHILD',
            'car_model_id' => 1,
        ]);
        $this->assertDatabaseMissing('register_shortages', ['item_no' => 'OTHER-CHILD']);
    }

    public function test_index_uses_the_paginated_resource_collection(): void
    {
        $parent = $this->createRegisterShortage();
        $this->createRegisterShortage([
            'item_no' => 'CHILD-1',
            'register_shortage_id' => $parent->id,
            'status' => RegisterShortage::STATUS_RESOLVED,
        ]);

        $this->getJson('/api/register-shortages')
            ->assertOk()
            ->assertJsonPath('data.0.vin', 'VIN-1')
            ->assertJsonPath('data.0.status', 'دارای کسری')
            ->assertJsonPath('data.0.children.0.status', 'رفع شده')
            ->assertJsonPath('data.0.children.0.children', [])
            ->assertJsonPath('data.0.vehicle.vin', 'VIN-1')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_store_rejects_an_existing_related_shortage_without_partial_inserts(): void
    {
        $mainItemId = $this->createMainItemShortage(1, 'MAIN-1', 'CHILD-1');
        DB::table('related_item_shortages')->insert([
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'CHILD-2',
            'description' => 'Second child',
        ]);
        $this->createRegisterShortage(['item_no' => 'CHILD-2']);

        $this->mock(ErpGetWorkCenterFromRoutingLink::class)
            ->shouldNotReceive('get');

        $this->postJson('/api/register-shortages', [
            'vin' => 'VIN-1',
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'MAIN-1',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 1,
            'routing_link_code' => 'LINK-1',
        ])->assertUnprocessable()->assertJsonValidationErrors('item_no');

        $this->assertDatabaseCount('register_shortages', 1);
        $this->assertDatabaseMissing('register_shortages', ['item_no' => 'MAIN-1']);
        $this->assertDatabaseMissing('register_shortages', ['item_no' => 'CHILD-1']);
    }

    public function test_store_rolls_back_when_related_items_repeat_the_same_item_number(): void
    {
        $mainItemId = $this->createMainItemShortage(1, 'MAIN-1', 'CHILD-1');
        DB::table('related_item_shortages')->insert([
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'CHILD-1',
            'description' => 'Repeated child',
        ]);

        $this->mock(ErpGetWorkCenterFromRoutingLink::class)
            ->shouldReceive('get')->once()->andReturn([]);

        $this->postJson('/api/register-shortages', [
            'vin' => 'VIN-1',
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'MAIN-1',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 1,
            'routing_link_code' => 'LINK-1',
        ])->assertUnprocessable()->assertJsonValidationErrors('item_no');

        $this->assertDatabaseCount('register_shortages', 0);
    }

    public function test_store_returns_the_parent_and_attaches_all_related_items_to_it(): void
    {
        $mainItemId = $this->createMainItemShortage(1, 'MAIN-1', 'CHILD-1');
        DB::table('related_item_shortages')->insert([
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'CHILD-2',
            'description' => 'Second child',
        ]);

        $this->mock(ErpGetWorkCenterFromRoutingLink::class)
            ->shouldReceive('get')->once()->andReturn([]);

        $response = $this->postJson('/api/register-shortages', [
            'vin' => 'VIN-1',
            'main_item_shortage_id' => $mainItemId,
            'item_no' => 'MAIN-1',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 1,
            'routing_link_code' => 'LINK-1',
        ])->assertCreated()
            ->assertJsonPath('data.item_no', 'MAIN-1')
            ->assertJsonCount(2, 'data.children');

        $parentId = $response->json('data.id');
        foreach (['CHILD-1', 'CHILD-2'] as $itemNo) {
            $this->assertDatabaseHas('register_shortages', [
                'item_no' => $itemNo,
                'register_shortage_id' => $parentId,
            ]);
        }
        $this->assertDatabaseCount('register_shortages', 3);
    }

    public function test_vin_and_item_number_must_be_unique_together(): void
    {
        $this->mock(ErpGetShortageBOM::class)
            ->shouldReceive('get')->twice()->andReturn([]);

        $payload = [
            'vin' => 'VIN-1',
            'item_no' => 'ITEM-1',
            'type' => RegisterShortage::TYPE_SINGLE,
            'qty' => 1,
        ];

        $this->postJson('/api/register-shortages', $payload)->assertCreated();
        $this->postJson('/api/register-shortages', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.item_no.0', 'این شماره قطعه قبلاً برای این خودرو ثبت شده است.');

        $this->postJson('/api/register-shortages', [
            ...$payload,
            'vin' => 'VIN-2',
        ])->assertCreated();
    }

    public function test_update_enforces_effective_values_and_clears_single_work_center(): void
    {
        $registerShortage = $this->createRegisterShortage([
            'type' => RegisterShortage::TYPE_MAIN,
            'work_center' => 'WC-10',
        ]);

        $this->patchJson("/api/register-shortages/{$registerShortage->id}", [
            'work_center' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors('work_center');

        $this->patchJson("/api/register-shortages/{$registerShortage->id}", [
            'type' => RegisterShortage::TYPE_SINGLE,
            'status' => RegisterShortage::STATUS_RESOLVED,
        ])->assertOk()
            ->assertJsonPath('data.work_center', null)
            ->assertJsonPath('data.status', 'رفع شده');
    }

    public function test_update_rejects_a_composite_duplicate_when_only_vin_changes(): void
    {
        $this->createRegisterShortage([
            'vin' => 'VIN-1',
            'item_no' => 'ITEM-1',
        ]);
        $registerShortage = $this->createRegisterShortage([
            'vin' => 'VIN-2',
            'item_no' => 'ITEM-1',
        ]);

        $this->patchJson("/api/register-shortages/{$registerShortage->id}", [
            'vin' => 'VIN-1',
        ])->assertUnprocessable()->assertJsonValidationErrors('item_no');
    }

    public function test_copy_filters_main_groups_and_preserves_single_and_child_attributes(): void
    {
        DB::table('vehicles')->where('vin', 'VIN-2')->update(['car_model_id' => 1]);

        $activeId = $this->createMainItemShortage(1, 'MAIN-ACTIVE', 'CATALOG-CHILD');
        $inactiveId = $this->createMainItemShortage(1, 'MAIN-INACTIVE', 'INACTIVE-CHILD');
        DB::table('main_item_shortages')->where('id', $inactiveId)->update(['is_active' => false]);
        $active = $this->createRegisterShortage([
            'main_item_shortage_id' => $activeId,
            'item_no' => 'MAIN-ACTIVE',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 3,
            'work_center' => 'WC-1',
            'is_certified' => true,
        ]);
        $child = $this->createRegisterShortage([
            'main_item_shortage_id' => $activeId,
            'register_shortage_id' => $active->id,
            'item_no' => 'REGISTERED-CHILD',
            'item_description' => 'Registered child description',
            'type' => RegisterShortage::TYPE_MAIN,
            'qty' => 4,
            'work_center' => 'WC-CHILD',
            'status' => RegisterShortage::STATUS_RESOLVED,
            'is_certified' => true,
        ]);
        $inactive = $this->createRegisterShortage([
            'main_item_shortage_id' => $inactiveId,
            'item_no' => 'MAIN-INACTIVE',
            'type' => RegisterShortage::TYPE_MAIN,
        ]);
        $this->createRegisterShortage([
            'main_item_shortage_id' => $inactiveId,
            'register_shortage_id' => $inactive->id,
            'item_no' => 'INACTIVE-CHILD',
            'type' => RegisterShortage::TYPE_SINGLE,
        ]);
        $this->createRegisterShortage([
            'item_no' => 'MAIN-WITHOUT-REFERENCE',
            'type' => RegisterShortage::TYPE_MAIN,
        ]);
        $this->createRegisterShortage([
            'main_item_shortage_id' => $activeId,
            'item_no' => 'MAIN-WITHOUT-CHILDREN',
            'type' => RegisterShortage::TYPE_MAIN,
        ]);
        $this->createRegisterShortage([
            'item_no' => 'SINGLE-1',
            'item_description' => 'Single description',
            'qty' => 5,
            'status' => RegisterShortage::STATUS_RESOLVED,
            'is_certified' => false,
        ]);
        $sourceRecords = DB::table('register_shortages')->where('vin', 'VIN-1')->orderBy('id')->get();

        $this->postJson('/api/shortages/VIN-1', ['vin' => 'VIN-2'])
            ->assertOk()->assertJsonCount(4, 'data');

        $copiedParent = RegisterShortage::query()->where('vin', 'VIN-2')->where('item_no', 'MAIN-ACTIVE')->firstOrFail();
        $this->assertNotSame($active->id, $copiedParent->id);
        $this->assertNull($copiedParent->register_shortage_id);
        $this->assertDatabaseHas('register_shortages', [
            'id' => $copiedParent->id,
            'main_item_shortage_id' => $activeId,
            'car_model_id' => 1,
            'make_code_id' => 2,
            'user_id' => $this->user->id,
            'qty' => 3,
            'work_center' => 'WC-1',
            'is_certified' => true,
        ]);
        $this->assertDatabaseHas('register_shortages', [
            'vin' => 'VIN-2',
            'item_no' => 'REGISTERED-CHILD',
            'item_description' => 'Registered child description',
            'register_shortage_id' => $copiedParent->id,
            'main_item_shortage_id' => $activeId,
            'car_model_id' => 1,
            'make_code_id' => 2,
            'qty' => 4,
            'work_center' => 'WC-CHILD',
            'status' => RegisterShortage::STATUS_RESOLVED,
            'is_certified' => true,
        ]);
        $this->assertDatabaseHas('register_shortages', [
            'vin' => 'VIN-2',
            'item_no' => 'SINGLE-1',
            'item_description' => 'Single description',
            'register_shortage_id' => null,
            'type' => RegisterShortage::TYPE_SINGLE,
            'qty' => 5,
            'status' => RegisterShortage::STATUS_RESOLVED,
            'is_certified' => false,
        ]);
        foreach (['MAIN-INACTIVE', 'INACTIVE-CHILD', 'MAIN-WITHOUT-REFERENCE', 'CATALOG-CHILD'] as $itemNo) {
            $this->assertDatabaseMissing('register_shortages', ['vin' => 'VIN-2', 'item_no' => $itemNo]);
        }
        $this->assertEquals($sourceRecords, DB::table('register_shortages')->where('vin', 'VIN-1')->orderBy('id')->get());
        $this->assertDatabaseHas('register_shortages', ['id' => $child->id, 'register_shortage_id' => $active->id]);
    }

    public function test_copy_rolls_back_all_inserts_when_a_child_already_exists_in_destination(): void
    {
        DB::table('vehicles')->where('vin', 'VIN-2')->update(['car_model_id' => 1]);

        $this->createRegisterShortage(['item_no' => 'SINGLE-1']);
        $mainId = $this->createMainItemShortage(1, 'MAIN-1', 'CHILD-1');
        $main = $this->createRegisterShortage([
            'main_item_shortage_id' => $mainId,
            'item_no' => 'MAIN-1',
            'type' => RegisterShortage::TYPE_MAIN,
        ]);
        $this->createRegisterShortage([
            'main_item_shortage_id' => $mainId,
            'register_shortage_id' => $main->id,
            'item_no' => 'CHILD-1',
            'type' => RegisterShortage::TYPE_MAIN,
        ]);
        $existing = $this->createRegisterShortage(['vin' => 'VIN-2', 'item_no' => 'CHILD-1']);

        $this->postJson('/api/shortages/VIN-1', ['vin' => 'VIN-2'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'شماره قطعه اصلی یا یکی از قطعات وابسته قبلاً برای خودروی مقصد ثبت شده است؛ هیچ کسری کپی نشد.');

        $this->assertSame([$existing->id], RegisterShortage::query()->where('vin', 'VIN-2')->pluck('id')->all());
        $this->assertDatabaseCount('register_shortages', 4);
    }

    public function test_copy_rejects_different_car_models_before_duplicate_item_checks(): void
    {
        $this->createRegisterShortage();
        $this->createRegisterShortage(['vin' => 'VIN-2']);
        $records = DB::table('register_shortages')->orderBy('id')->get();

        $this->postJson('/api/shortages/VIN-1', ['vin' => 'VIN-2'])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'دو شاسی از یک نوع نیستند.']);

        $this->assertEquals($records, DB::table('register_shortages')->orderBy('id')->get());
    }

    public function test_copy_rejects_different_car_models_even_without_source_shortages(): void
    {
        $this->postJson('/api/shortages/VIN-1', ['vin' => 'VIN-2'])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'دو شاسی از یک نوع نیستند.']);

        $this->assertDatabaseCount('register_shortages', 0);
    }

    public function test_copy_rejects_the_source_vin_as_destination(): void
    {
        $this->createRegisterShortage();

        $this->postJson('/api/shortages/VIN-1', ['vin' => 'VIN-1'])->assertUnprocessable();

        $this->assertDatabaseCount('register_shortages', 1);
    }

    public function test_copy_validates_destination_vin(): void
    {
        $this->createRegisterShortage();

        $this->postJson('/api/shortages/VIN-1', [])->assertUnprocessable()->assertJsonValidationErrors('vin');
        $this->postJson('/api/shortages/VIN-1', ['vin' => 'UNKNOWN'])->assertUnprocessable()->assertJsonValidationErrors('vin');

        $this->assertDatabaseCount('register_shortages', 1);
    }

    public function test_copy_with_no_source_shortages_returns_existing_destination_records(): void
    {
        DB::table('vehicles')->where('vin', 'VIN-2')->update(['car_model_id' => 1]);

        $existing = $this->createRegisterShortage(['vin' => 'VIN-2']);

        $this->postJson('/api/shortages/VIN-1', ['vin' => 'VIN-2'])
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $existing->id);

        $this->assertDatabaseCount('register_shortages', 1);
    }

    private function createRegisterShortage(array $attributes = []): RegisterShortage
    {
        $vin = $attributes['vin'] ?? 'VIN-1';
        $vehicle = DB::table('vehicles')->where('vin', $vin)->first();

        return RegisterShortage::query()->create(array_merge([
            'vin' => $vin,
            'car_model_id' => $vehicle->car_model_id,
            'make_code_id' => $vehicle->make_code_id,
            'item_no' => 'ITEM-1',
            'item_description' => null,
            'type' => RegisterShortage::TYPE_SINGLE,
            'qty' => 1,
            'work_center' => null,
            'status' => RegisterShortage::STATUS_HAS_SHORTAGE,
            'user_id' => $this->user->id,
        ], $attributes));
    }

    private function seedReferenceData(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        DB::table('car_models')->insert([
            ['id' => 1, 'reference' => 'CAR-1', 'title' => 'Car 1'],
            ['id' => 2, 'reference' => 'CAR-2', 'title' => 'Car 2'],
        ]);
        DB::table('make_codes')->insert([
            ['id' => 1, 'reference' => 'MAKE-1', 'title' => 'Make 1'],
            ['id' => 2, 'reference' => 'MAKE-2', 'title' => 'Make 2'],
        ]);
        DB::table('vehicles')->insert([
            [
                'vin' => 'VIN-1',
                'production_order_number' => 'PO-1',
                'car_model_id' => 1,
                'make_code_id' => 1,
            ],
            [
                'vin' => 'VIN-2',
                'production_order_number' => 'PO-2',
                'car_model_id' => 2,
                'make_code_id' => 2,
            ],
        ]);
    }

    private function createMainItemShortage(int $carModelId, string $itemNo, string $relatedItemNo): int
    {
        $id = DB::table('main_item_shortages')->insertGetId([
            'car_model_id' => $carModelId,
            'item_no' => $itemNo,
            'is_active' => true,
        ]);
        DB::table('related_item_shortages')->insert([
            'main_item_shortage_id' => $id,
            'item_no' => $relatedItemNo,
            'description' => $relatedItemNo,
        ]);

        return $id;
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('car_models', function (Blueprint $table): void {
            $table->id();
            $table->string('reference');
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('make_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('reference');
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->string('vin')->primary();
            $table->string('production_order_number')->unique();
            $table->foreignId('car_model_id');
            $table->foreignId('make_code_id');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('car_model_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('car_model_id')->nullable();
            $table->timestamps();
        });
        Schema::create('register_shortages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('main_item_shortage_id')->nullable();
            $table->foreignId('register_shortage_id')->nullable();
            $table->string('vin');
            $table->foreignId('car_model_id');
            $table->foreignId('make_code_id');
            $table->string('item_no');
            $table->text('item_description')->nullable();
            $table->string('type');
            $table->unsignedInteger('qty');
            $table->string('work_center')->nullable();
            $table->string('status')->default(RegisterShortage::STATUS_HAS_SHORTAGE);
            $table->foreignId('user_id');
            $table->boolean('is_certified')->default(false);
            $table->unique(['vin', 'item_no']);
            $table->timestamps();
        });
        Schema::create('main_item_shortages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('car_model_id');
            $table->string('item_no');
            $table->boolean('is_active');
            $table->timestamps();
        });
        Schema::create('related_item_shortages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('main_item_shortage_id');
            $table->string('item_no');
            $table->text('description');
            $table->timestamps();
        });
    }
}
