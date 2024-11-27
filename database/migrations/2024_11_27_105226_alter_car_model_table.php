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
            $table->string('cabin_serial_chr_1')->nullable()->after('id');
            $table->string('cabin_serial_chr_2')->nullable()->after('id');
            $table->foreignId('year_map_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            //
        });
    }
};
