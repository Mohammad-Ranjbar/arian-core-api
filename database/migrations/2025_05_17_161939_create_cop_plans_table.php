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
        Schema::create('cop_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('car_model_cop_plan', function (Blueprint $table) {
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cop_plan_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_model_cop_plan');
        Schema::dropIfExists('cop_plans');
    }
};
