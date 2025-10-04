<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_tracking', function (Blueprint $table) {
            $table->boolean('is_systemic')->after('id')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_tracking', function (Blueprint $table) {
            $table->dropColumn('is_systemic');
        });
    }
};
