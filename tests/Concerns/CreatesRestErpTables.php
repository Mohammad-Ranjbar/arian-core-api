<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait CreatesRestErpTables
{
    protected function createRestErpTables(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', true);
        config()->set('cache.default', 'array');
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        DB::table('users')->insert([
            ['id' => 42, 'name' => 'Request actor'],
            ['id' => 43, 'name' => 'Retry actor'],
        ]);
        foreach ([
            '2023_10_29_103259_create_permission_tables.php',
            '2023_11_19_153440_create_action_logs_table.php',
            '2024_05_25_110942_alter_data_to_action_logs_table.php',
            '2024_11_17_101057_create_erp_jobs_table.php',
            '2023_11_11_121219_create_baskets_table.php',
            '2026_10_12_130000_create_rest_erp_jobs_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $this->actingAs(User::query()->findOrFail(42));
    }
}
