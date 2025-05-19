<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('vehicle_torque_tests', function (Blueprint $table) {
            $table->foreignId('vehicle_torque_test_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_torque_tests', function (Blueprint $table) {
            $table->dropForeign('vehicle_torque_tests_vehicle_torque_test_id_foreign');
            $table->dropIndex('vehicle_torque_tests_vehicle_torque_test_id_foreign');
            $table->dropColumn('vehicle_torque_test_id');
        });
    }
};
