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
            $table->dropForeign('vehicle_torque_tests_torque_meter_id_foreign');
            $table->dropForeign('newIndexTest');
            $table->dropColumn('torque_measuring_equipment_id');
            $table->dropColumn('torque_meter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_torque_tests', function (Blueprint $table) {
            //
        });
    }
};
