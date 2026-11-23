<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveUserMiddleware;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ListAllAlternativesAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        (require database_path('migrations/2023_10_29_103259_create_permission_tables.php'))->up();
        Schema::create('alternatives', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
        $this->withoutMiddleware(CheckActiveUserMiddleware::class);
    }

    public function test_non_super_admin_is_forbidden_even_with_view_permission(): void
    {
        $this->actingAsUserWithRole(false);
        Gate::before(fn () => true);

        $this->getJson('/api/alternatives-list/all?options=1')->assertForbidden();
    }

    public function test_super_admin_with_view_permission_can_access_the_list(): void
    {
        $this->actingAsUserWithRole(true);
        Gate::before(fn () => true);

        $this->getJson('/api/alternatives-list/all')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_super_admin_still_requires_view_permission(): void
    {
        $this->actingAsUserWithRole(true);
        Gate::before(fn () => false);

        $this->getJson('/api/alternatives-list/all')->assertForbidden();
    }

    private function actingAsUserWithRole(bool $hasRole): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->with('super-admin')->andReturn($hasRole);
        $this->actingAs($user, 'sanctum');
    }
}
