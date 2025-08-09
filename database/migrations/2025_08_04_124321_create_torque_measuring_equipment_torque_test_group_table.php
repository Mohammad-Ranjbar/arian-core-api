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
        Schema::create('torque_measuring_equipment_torque_test_group', function (Blueprint $table) {
            $table->foreignId('torque_measuring_equipment_id')
                ->constrained('torque_measuring_equipment','id','newIndex')
                ->cascadeOnDelete();
            $table->foreignId('torque_test_group_id')->constrained('torque_test_groups','id','newIndexGroup')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('torque_measuring_equipment_torque_test_group');
    }
};
