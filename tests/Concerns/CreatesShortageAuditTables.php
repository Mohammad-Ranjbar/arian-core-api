<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait CreatesShortageAuditTables
{
    protected function createShortageAuditTablesAndAuthenticate(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('action_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->string('model_id');
            $table->string('action');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Audit User',
        ]);

        $this->actingAs(User::query()->findOrFail(1));
    }
}
