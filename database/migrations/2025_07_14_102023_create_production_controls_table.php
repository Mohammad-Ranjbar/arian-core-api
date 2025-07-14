<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_controls', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->uuid('file_id')->nullable();
            $table->timestamps();
        });
        Schema::create('car_model_production_control', function (Blueprint $table) {
            $table->foreignId('production_control_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
        });

    }

    public function down(): void
    {

        Schema::dropIfExists('car_model_production_control');
        Schema::dropIfExists('production_controls');
    }
};
