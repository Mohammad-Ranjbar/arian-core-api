<?php

namespace Tests\Feature;

use App\Models\IncidentSourceSelection;
use App\Models\Location;
use App\Models\Salon;
use App\Models\User;
use App\Models\WorkGroupCenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IncidentSourceSelectionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
        foreach (['car_models', 'elements', 'incidents', 'salons', 'production_lines', ...array_keys(IncidentSourceSelection::MODEL_TYPES)] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->string('title');
                if ($name === 'work_group_centers') {
                    $table->unsignedBigInteger('production_line_id')->nullable();
                }
                $table->softDeletes();
                $table->timestamps();
            });
            DB::table($name)->insert([
                ['id' => 1, 'title' => 'اول'], ['id' => 2, 'title' => 'دوم'],
            ]);
        }
        foreach ([1, 2] as $id) {
            DB::table('work_group_centers')->where('id', $id)->update(['production_line_id' => $id]);
        }
        Schema::create('car_model_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id');
        });
        (require database_path('migrations/2023_11_04_123030_create_incident_sources_table.php'))->up();
        DB::table('incident_sources')->insert([
            'id' => 1, 'car_model_id' => 1, 'element_id' => 1, 'incident_id' => 1,
            'model_type' => Salon::class, 'model_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->migration()->up();
        DB::table('users')->insert([['id' => 1, 'name' => 'اول'], ['id' => 2, 'name' => 'دوم']]);
        $this->actingAs(User::findOrFail(1));
        (require database_path('migrations/2023_10_29_103259_create_permission_tables.php'))->up();
        foreach (['update', 'show', 'index'] as $action) {
            $permission = Permission::create(['name' => 'incident-sources.'.$action, 'guard_name' => 'web']);
            User::findOrFail(1)->givePermissionTo($permission);
            User::findOrFail(2)->givePermissionTo($permission);
        }
    }

    public function test_migration_preserves_legacy_source_without_inventing_an_author(): void
    {
        $this->assertDatabaseHas('incident_source_selections', [
            'incident_source_id' => 1, 'model_type' => Salon::class, 'model_id' => 1, 'user_id' => null,
        ]);
        $this->migration()->down();
        $this->assertFalse(Schema::hasTable('incident_source_selections'));
        $this->assertDatabaseHas('incident_sources', ['id' => 1, 'model_id' => 1]);
    }

    public function test_multiple_selections_for_every_model_and_authenticated_author_are_returned(): void
    {
        foreach (array_keys(IncidentSourceSelection::MODEL_TYPES) as $type) {
            $sources = [
                ['model_type' => $type, 'model_id' => 1],
                ['model_type' => $type, 'model_id' => 2],
            ];
            $response = $this->putJson('/api/incident-sources/1', ['sources' => $sources, 'user_id' => 2])
                ->assertOk()->assertJsonCount(2, 'data.sources');
            $response->assertJsonPath('data.sources.0.model_type', $type)
                ->assertJsonPath('data.sources.1.model_type', $type)
                ->assertJsonPath('data.sources.0.user_id', 1)
                ->assertJsonPath('data.sources.1.user_id', 1);
            $this->assertDatabaseCount('incident_source_selections', 2);
            $this->getJson('/api/incident-sources/1')->assertOk()->assertJsonCount(2, 'data.sources');
            $this->getJson('/api/incident-sources')->assertOk()->assertJsonCount(2, 'data.0.sources');
        }
    }

    public function test_mixed_model_types_are_rejected_without_changing_existing_selections(): void
    {
        foreach (['locations'] as $type) {
            $response = $this->putJson('/api/incident-sources/1', ['sources' => [
                ['model_type' => 'work_group_centers', 'model_id' => 1],
                ['model_type' => $type, 'model_id' => 2],
            ]])->assertUnprocessable()->assertJsonValidationErrors('sources.1.model_type');
            $this->assertSame('تمام منشأهای ایراد انتخاب‌شده باید از یک نوع باشند.', $response->json('errors')['sources.1.model_type'][0]);
            $this->assertDatabaseCount('incident_source_selections', 1);
            $this->assertDatabaseHas('incident_source_selections', [
                'incident_source_id' => 1, 'model_type' => Salon::class, 'model_id' => 1, 'user_id' => null,
            ]);
        }
    }

    public function test_removed_model_types_are_rejected_without_changing_existing_selections(): void
    {
        foreach (['salons', 'production_lines', 'stations'] as $type) {
            $response = $this->putJson('/api/incident-sources/1', ['sources' => [
                ['model_type' => $type, 'model_id' => 1],
            ]])->assertUnprocessable()->assertJsonValidationErrors('sources.0.model_type');
            $this->assertSame('نوع منشأ ایراد باید مرکز گروه کاری یا موقعیت باشد.', $response->json('errors')['sources.0.model_type'][0]);
            $this->assertDatabaseCount('incident_source_selections', 1);
            $this->assertDatabaseHas('incident_source_selections', ['model_type' => Salon::class, 'model_id' => 1]);
        }
    }

    public function test_replacement_removes_old_items_preserves_authors_and_retries_are_idempotent(): void
    {
        $payload = ['sources' => [
            ['model_type' => 'work_group_centers', 'model_id' => 1],
            ['model_type' => 'work_group_centers', 'model_id' => 2],
        ]];
        $first = $this->putJson('/api/incident-sources/1', $payload)->assertOk()->json('data.sources');
        $this->actingAs(User::findOrFail(2));
        $this->putJson('/api/incident-sources/1', $payload)->assertOk()->assertJsonPath('data.sources', $first);
        $this->assertDatabaseCount('incident_source_selections', 2);
        $this->putJson('/api/incident-sources/1', ['sources' => [$payload['sources'][1]]])
            ->assertOk()->assertJsonCount(1, 'data.sources')->assertJsonPath('data.sources.0.user_id', 1);
        $this->putJson('/api/incident-sources/1', ['sources' => []])->assertOk()->assertJsonCount(0, 'data.sources');
        $this->assertDatabaseCount('incident_source_selections', 0);
        $this->assertDatabaseHas('incident_sources', ['id' => 1, 'model_type' => null, 'model_id' => null]);
    }

    public function test_invalid_inputs_return_persian_validation_errors_without_writing(): void
    {
        $cases = [
            [[], 'sources', 'ارسال فهرست منشأهای ایراد الزامی است.'],
            [['sources' => 'invalid'], 'sources', 'فهرست منشأهای ایراد باید آرایه باشد.'],
            [['sources' => [['model_type' => 'users', 'model_id' => 1]]], 'sources.0.model_type', 'نوع منشأ ایراد باید مرکز گروه کاری یا موقعیت باشد.'],
            [['sources' => [['model_type' => 'work_group_centers', 'model_id' => 999]]], 'sources.0.model_id', 'منشأ ایراد انتخاب‌شده برای این نوع وجود ندارد یا حذف شده است.'],
            [['sources' => [['model_type' => 'work_group_centers', 'model_id' => 'bad']]], 'sources.0.model_id', 'شناسهٔ منشأ ایراد باید عدد صحیح باشد.'],
            [['sources' => [['model_type' => 'work_group_centers']]], 'sources.0.model_id', 'انتخاب شناسهٔ منشأ ایراد الزامی است.'],
            [['sources' => [null]], 'sources.0', 'مشخصات هر منشأ ایراد الزامی است.'],
            [['sources' => [['model_type' => ['work_group_centers'], 'model_id' => 1]]], 'sources.0.model_type', 'نوع منشأ ایراد باید رشتهٔ متنی باشد.'],
            [['sources' => [['model_type' => 'work_group_centers', 'model_id' => 1], ['model_type' => 'work_group_centers', 'model_id' => '1']]], 'sources.1.model_id', 'این منشأ ایراد در فهرست انتخاب‌ها تکراری است.'],
            [['sources' => [['model_type' => 'work_group_centers', 'model_id' => 1, 'user_id' => 2]]], 'sources.0', 'هر منشأ ایراد باید فقط شامل نوع و شناسهٔ منشأ باشد.'],
        ];
        foreach ($cases as [$payload, $field, $message]) {
            $response = $this->putJson('/api/incident-sources/1', $payload)->assertUnprocessable()
                ->assertJsonValidationErrors($field);
            $this->assertSame($message, $response->json('errors')[$field][0]);
        }
        DB::table('work_group_centers')->where('id', 2)->update(['deleted_at' => now()]);
        $this->putJson('/api/incident-sources/1', ['sources' => [['model_type' => 'work_group_centers', 'model_id' => 2]]])
            ->assertUnprocessable()->assertJsonValidationErrors('sources.0.model_id');
        $this->assertDatabaseCount('incident_source_selections', 1);
    }

    public function test_an_in_flight_user_request_is_rejected_and_lock_is_released_after_success(): void
    {
        $lock = Cache::lock('incident-source-update:user:1', 30);
        $this->assertTrue($lock->get());
        $this->putJson('/api/incident-sources/1', ['sources' => []])->assertStatus(429);
        $this->assertDatabaseCount('incident_source_selections', 1);
        $lock->release();
        $this->putJson('/api/incident-sources/1', ['sources' => []])->assertOk();
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_failed_replacement_rolls_back_and_releases_the_user_lock(): void
    {
        IncidentSourceSelection::creating(function ($selection) {
            if ($selection->model_id === 2) {
                throw new \RuntimeException('Simulated write failure');
            }
        });
        try {
            $this->putJson('/api/incident-sources/1', ['sources' => [
                ['model_type' => 'work_group_centers', 'model_id' => 1],
                ['model_type' => 'work_group_centers', 'model_id' => 2],
            ]])->assertStatus(500);
            $this->assertDatabaseCount('incident_source_selections', 1);
            $this->assertDatabaseHas('incident_source_selections', ['model_type' => Salon::class, 'model_id' => 1]);
            $lock = Cache::lock('incident-source-update:user:1', 30);
            $this->assertTrue($lock->get());
            $lock->release();
        } finally {
            IncidentSourceSelection::flushEventListeners();
        }
    }

    public function test_database_rejects_duplicate_model_pairs(): void
    {
        $this->expectException(QueryException::class);
        DB::table('incident_source_selections')->insert([
            'incident_source_id' => 1, 'model_type' => Salon::class, 'model_id' => 1, 'user_id' => 1,
        ]);
    }

    public function test_unauthenticated_updates_are_rejected(): void
    {
        $this->app['auth']->forgetGuards();
        $this->putJson('/api/incident-sources/1', ['sources' => []])->assertUnauthorized();
    }

    public function test_resource_includes_selection_author_and_handles_deleted_relations(): void
    {
        $this->putJson('/api/incident-sources/1', ['sources' => [
            ['model_type' => 'work_group_centers', 'model_id' => 2],
        ]])->assertOk()->assertJsonPath('data.sources.0.user', ['id' => 1, 'name' => 'اول'])
            ->assertJsonPath('data.model_type', 'work_group_centers')->assertJsonPath('data.model_id', 2)
            ->assertJsonPath('data.source', 'دوم');

        DB::table('work_group_centers')->where('id', 2)->update(['deleted_at' => now()]);
        DB::table('elements')->where('id', 1)->update(['deleted_at' => now()]);
        DB::table('incident_source_selections')->update(['user_id' => 2]);
        DB::table('users')->where('id', 2)->update(['deleted_at' => now()]);
        $this->getJson('/api/incident-sources/1')->assertOk()
            ->assertJsonPath('data.sources.0.model_type', 'work_group_centers')
            ->assertJsonPath('data.sources.0.model_id', 2)
            ->assertJsonPath('data.sources.0.source', '')
            ->assertJsonPath('data.sources.0.user_id', 2)
            ->assertJsonPath('data.sources.0.user', null)
            ->assertJsonPath('data.element.id', null);
    }

    public function test_list_eager_loads_relations_with_constant_query_count_as_rows_grow(): void
    {
        $seed = function (int $id) {
            DB::table('incident_sources')->insert([
                'id' => $id, 'car_model_id' => 1, 'element_id' => 1, 'incident_id' => 1,
            ]);
            DB::table('incident_source_selections')->insert([
                'incident_source_id' => $id,
                'model_type' => $id % 2 === 0 ? WorkGroupCenter::class : Location::class,
                'model_id' => 1, 'user_id' => 1,
            ]);
        };
        $seed(2);
        $seed(3);
        $this->getJson('/api/incident-sources')->assertOk();

        Model::preventLazyLoading();
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $this->getJson('/api/incident-sources')->assertOk()->assertJsonCount(3, 'data');
            $small = DB::getQueryLog();
            foreach (range(4, 13) as $id) {
                $seed($id);
            }
            DB::flushQueryLog();
            $this->getJson('/api/incident-sources')->assertOk()->assertJsonCount(13, 'data')
                ->assertJsonPath('data.0.id', 13)
                ->assertJsonPath('data.0.sources.0.user.name', 'اول');
            $large = DB::getQueryLog();
            $this->assertSame(count($small), count($large));
            $this->assertNotEmpty($large);
            foreach ($large as $query) {
                $this->assertStringNotContainsString('car_model_locations', $query['query']);
            }
        } finally {
            DB::disableQueryLog();
            Model::preventLazyLoading(false);
        }
    }

    public function test_list_validates_pagination_and_preserves_filters_in_links(): void
    {
        foreach ([
            ['paginate=0', 'paginate', 'تعداد رکورد در هر صفحه باید حداقل ۱ باشد.'],
            ['paginate=101', 'paginate', 'تعداد رکورد در هر صفحه نباید بیشتر از ۱۰۰ باشد.'],
            ['paginate=invalid', 'paginate', 'تعداد رکورد در هر صفحه باید عدد صحیح باشد.'],
            ['paginate[]=1', 'paginate', 'تعداد رکورد در هر صفحه باید عدد صحیح باشد.'],
            ['page=0', 'page', 'شمارهٔ صفحه باید حداقل ۱ باشد.'],
            ['page=invalid', 'page', 'شمارهٔ صفحه باید عدد صحیح باشد.'],
        ] as [$query, $field, $message]) {
            $this->getJson('/api/incident-sources?'.$query)->assertUnprocessable()
                ->assertJsonPath('errors.'.$field.'.0', $message);
        }
        $this->getJson('/api/incident-sources?paginate=1&car_model_id=1')->assertOk()
            ->assertJsonPath('meta.per_page', 1)->assertJsonPath('meta.total', 1)
            ->assertJsonPath('links.first', url('/api/incident-sources').'?paginate=1&car_model_id=1&page=1');
        $this->getJson('/api/incident-sources?car_model_id=2')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_list_filters_all_selections_by_type_and_exact_id(): void
    {
        $this->putJson('/api/incident-sources/1', ['sources' => [
            ['model_type' => 'work_group_centers', 'model_id' => 1],
            ['model_type' => 'work_group_centers', 'model_id' => 2],
        ]])->assertOk();
        DB::table('incident_sources')->insert([
            ['id' => 2, 'car_model_id' => 1, 'element_id' => 1, 'incident_id' => 1, 'model_type' => Location::class, 'model_id' => 1],
            ['id' => 3, 'car_model_id' => 1, 'element_id' => 1, 'incident_id' => 1, 'model_type' => WorkGroupCenter::class, 'model_id' => 2],
            ['id' => 4, 'car_model_id' => 1, 'element_id' => 1, 'incident_id' => 1, 'model_type' => WorkGroupCenter::class, 'model_id' => 2],
        ]);
        DB::table('work_group_centers')->insert(['id' => 12, 'title' => 'دوازدهم']);
        DB::table('incident_source_selections')->insert([
            ['incident_source_id' => 2, 'model_type' => Location::class, 'model_id' => 1, 'user_id' => 1],
            ['incident_source_id' => 2, 'model_type' => Location::class, 'model_id' => 2, 'user_id' => 1],
            ['incident_source_id' => 3, 'model_type' => WorkGroupCenter::class, 'model_id' => 12, 'user_id' => 1],
        ]);

        $this->getJson('/api/incident-sources?model_id=2')->assertOk()
            ->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', 2)->assertJsonPath('data.1.id', 1);
        $this->getJson('/api/incident-sources?model_type=locations')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', 2);
        $this->getJson('/api/incident-sources?model_type=work_group_centers')->assertOk()
            ->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', 3)->assertJsonPath('data.1.id', 1);
        $this->getJson('/api/incident-sources?model_type=work_group_centers&model_id=2&paginate=1')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', 1)
            ->assertJsonCount(2, 'data.0.sources')
            ->assertJsonPath('links.first', url('/api/incident-sources').'?model_type=work_group_centers&model_id=2&paginate=1&page=1');
        $this->getJson('/api/incident-sources?model_type=locations&model_id=2')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', 2);
        $this->getJson('/api/incident-sources?model_id=999')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_combined_selection_filters_must_match_the_same_selection(): void
    {
        // Historical data can contain different types; each pair must match one row.
        DB::table('incident_source_selections')->delete();
        DB::table('incident_source_selections')->insert([
            ['incident_source_id' => 1, 'model_type' => WorkGroupCenter::class, 'model_id' => 1],
            ['incident_source_id' => 1, 'model_type' => Location::class, 'model_id' => 2],
        ]);
        $this->getJson('/api/incident-sources?model_type=work_group_centers')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/incident-sources?model_id=2')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/incident-sources?model_type=work_group_centers&model_id=2')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_list_rejects_invalid_selection_filters_with_persian_messages(): void
    {
        foreach ([
            ['model_type=salons', 'model_type', 'نوع منشأ ایراد باید مرکز گروه کاری یا موقعیت باشد.'],
            ['model_type=invalid', 'model_type', 'نوع منشأ ایراد باید مرکز گروه کاری یا موقعیت باشد.'],
            ['model_type[]=locations', 'model_type', 'نوع منشأ ایراد باید رشتهٔ متنی باشد.'],
            ['model_id=invalid', 'model_id', 'شناسهٔ منشأ ایراد باید عدد صحیح باشد.'],
            ['model_id[]=2', 'model_id', 'شناسهٔ منشأ ایراد باید عدد صحیح باشد.'],
            ['model_id=0', 'model_id', 'شناسهٔ منشأ ایراد باید بزرگ‌تر از صفر باشد.'],
            ['model_id=-1', 'model_id', 'شناسهٔ منشأ ایراد باید بزرگ‌تر از صفر باشد.'],
        ] as [$query, $field, $message]) {
            $this->getJson('/api/incident-sources?'.$query)->assertUnprocessable()
                ->assertJsonPath('errors.'.$field.'.0', $message);
        }
    }

    public function test_work_group_centers_include_production_line_in_update_show_and_list(): void
    {
        $payload = ['sources' => [
            ['model_type' => 'work_group_centers', 'model_id' => 1],
            ['model_type' => 'work_group_centers', 'model_id' => 2],
        ]];
        $firstLine = ['id' => 1, 'title' => 'اول'];
        $secondLine = ['id' => 2, 'title' => 'دوم'];
        $this->putJson('/api/incident-sources/1', $payload)->assertOk()
            ->assertJsonPath('data.sources.0.production_line', $firstLine)
            ->assertJsonPath('data.sources.1.production_line', $secondLine);
        $this->getJson('/api/incident-sources/1')->assertOk()
            ->assertJsonPath('data.sources.0.production_line', $firstLine)
            ->assertJsonPath('data.sources.1.production_line', $secondLine);
        $this->getJson('/api/incident-sources')->assertOk()
            ->assertJsonPath('data.0.sources.0.production_line', $firstLine)
            ->assertJsonPath('data.0.sources.1.production_line', $secondLine);

        DB::table('production_lines')->where('id', 1)->update(['deleted_at' => now()]);
        DB::table('work_group_centers')->where('id', 2)->update(['production_line_id' => null]);
        $this->getJson('/api/incident-sources/1')->assertOk()
            ->assertJsonPath('data.sources.0.production_line', null)
            ->assertJsonPath('data.sources.1.production_line', null);
        $this->putJson('/api/incident-sources/1', ['sources' => [
            ['model_type' => 'locations', 'model_id' => 1],
        ]])->assertOk()->assertJsonPath('data.sources.0.production_line', null);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_16_000000_create_incident_source_selections_table.php');
    }
}
