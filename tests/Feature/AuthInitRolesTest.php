<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthInitRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        (require database_path('migrations/2023_10_29_103259_create_permission_tables.php'))->up();

        foreach (['make_codes', 'locations'] as $table) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
            });
        }

        foreach (['make_code_user' => 'make_code_id', 'location_user' => 'location_id'] as $table => $foreignKey) {
            Schema::create($table, function (Blueprint $table) use ($foreignKey): void {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger($foreignKey);
            });
        }

        $this->withoutMiddleware(CheckActiveUserMiddleware::class);
        $user = new User();
        $user->id = 1;
        $this->actingAs($user, 'sanctum');
    }

    public function test_init_returns_the_authenticated_users_role_titles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'مدیر', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'کارشناس', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'نقش کاربر دیگر', 'guard_name' => 'web'],
        ]);
        DB::table('model_has_roles')->insert([
            ['role_id' => 1, 'model_type' => User::class, 'model_id' => 1],
            ['role_id' => 2, 'model_type' => User::class, 'model_id' => 1],
            ['role_id' => 3, 'model_type' => User::class, 'model_id' => 2],
        ]);

        $response = $this->getJson('/api/auth/init')->assertOk();

        $this->assertEqualsCanonicalizing(['مدیر', 'کارشناس'], $response->json('roles'));
        $this->assertEqualsCanonicalizing([1, 2], $response->json('user.roles'));
    }

    public function test_init_returns_an_empty_array_when_the_user_has_no_roles(): void
    {
        $this->getJson('/api/auth/init')
            ->assertOk()
            ->assertJsonPath('roles', [])
            ->assertJsonPath('user.roles', []);
    }
}
