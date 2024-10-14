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
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            $table->index('type');
            $table->index('intensity');
            $table->index('method');
            $table->index('checked_at');
            $table->index('created_at');
            $table->index('vin_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            //
        });
    }
};
