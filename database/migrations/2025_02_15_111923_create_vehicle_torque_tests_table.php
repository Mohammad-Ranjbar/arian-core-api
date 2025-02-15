<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('vehicle_torque_tests', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->index();
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('screw_id')->constrained()->cascadeOnDelete();
            $table->float('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_torque_tests');
    }
};
