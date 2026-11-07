<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->unique();
            $table->foreign('vin')->references('vin')->on('vehicles')->restrictOnDelete()->cascadeOnUpdate();
            $table->json('data');
            $table->boolean('is_complete')->default(false);
            $table->string('production_order_number');
            $table->string('variant_code');
            $table->foreignId('car_model_id')->constrained()->restrictOnDelete();
            $table->foreignId('make_code_id')->constrained()->restrictOnDelete();
            $table->date('due_date');
            $table->json('erp_response')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_allocations');
    }
};
