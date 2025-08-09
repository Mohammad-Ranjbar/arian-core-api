<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_torque_tests', function (Blueprint $table) {
            $table->dropForeign('vehicle_torque_tests_vehicle_torque_test_id_foreign');
            $table->dropColumn('vehicle_torque_test_id');


            $table->foreignId('torque_measuring_equipment_id')
                ->after('id')
                ->constrained('torque_measuring_equipment','id','newIndexTest')
                ->cascadeOnDelete();

            $table->foreignId('torque_meter_id')->after('id')->constrained()->cascadeOnDelete();

            $table->foreignId('torque_test_group_id')->after('id')->constrained()->cascadeOnDelete();

            $table->text('description')->after('id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_torque_tests', function (Blueprint $table) {
//            $table->foreignId('vehicle_torque_test_id')->after('id')->constrained()->cascadeOnDelete();

            $table->dropForeign('newIndexTest');
            $table->dropColumn('torque_measuring_equipment_id');

            $table->dropForeign('vehicle_torque_tests_torque_meter_id_foreign');
            $table->dropColumn('torque_meter_id');


            $table->dropForeign('vehicle_torque_tests_torque_test_group_id_foreign');
            $table->dropColumn('torque_test_group_id');


            $table->dropColumn('description');

        });
    }
};
