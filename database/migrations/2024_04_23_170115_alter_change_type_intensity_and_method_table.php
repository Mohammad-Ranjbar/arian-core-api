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
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            $table->string('intensity')->nullable()->change();
            $table->string('method')->nullable()->change();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('vehicle_faults', function (Blueprint $table) {
            //
        });
    }
};
