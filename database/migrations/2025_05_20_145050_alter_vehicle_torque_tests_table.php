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
        Schema::table('vehicle_torque_tests', function (Blueprint $table) {
            $table->string('range_color')->nullable();
            $table->string('range_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_torque_tests', function (Blueprint $table) {
            $table->dropColumn('range_color');
            $table->dropColumn('range_code');
        });
    }
};
