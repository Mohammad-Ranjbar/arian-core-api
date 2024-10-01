<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            $table->string('vin_type')->default('vehicle');
        });
    }


    public function down(): void
    {
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            //
        });
    }
};
