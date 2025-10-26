<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('torque_test_groups');
        Schema::enableForeignKeyConstraints();
        Schema::create('torque_test_groups', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['date', 'car_model_id']);
            $table->timestamps();
        });

        Schema::create('torque_meter_torque_test_group', function (Blueprint $table) {
            $table->foreignId('torque_test_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('torque_meter_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('torque_test_groups');
        Schema::dropIfExists('torque_meter_torque_test_group');
        Schema::enableForeignKeyConstraints();
    }
};
