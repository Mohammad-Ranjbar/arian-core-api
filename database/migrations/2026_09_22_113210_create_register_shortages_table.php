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
        Schema::create('register_shortages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_shortage_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('vin')->index();
            $table->foreign('vin')->references('vin')->on('vehicles')->restrictOnDelete();
            $table->foreignId('car_model_id')->index()->constrained('car_models')->restrictOnDelete();
            $table->foreignId('make_code_id')->index()->constrained('make_codes')->restrictOnDelete();
            $table->string('item_no')->index();
            $table->text('item_description')->nullable();
            $table->enum('type',['main','single'])->index();
            $table->unsignedInteger('qty');
            $table->string('work_center')->index()->nullable()->comment('required when type is main');
            $table->enum('status',['has_shortage','resolve_shortage'])->default('has_shortage')->index();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unique(['vin','item_no']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('register_shortages');
    }
};
