<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            $table->foreignId('make_code_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            $table->dropForeign('make_code_id');
        });
    }
};
