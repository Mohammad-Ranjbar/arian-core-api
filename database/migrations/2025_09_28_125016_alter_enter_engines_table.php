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
        Schema::table('enter_engines', function (Blueprint $table) {
            $table->foreignId('make_code_id')->nullable()->constrained('make_codes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enter_engines', function (Blueprint $table) {
            $table->dropForeign('enter_engines_make_code_id_foreign');
            $table->dropColumn('make_code_id');
        });
    }
};
