<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specific_shortage_items', function (Blueprint $table) {
            $table->id();
            $table->string('qc_code')->default('84');
            $table->string('item_no')->index();
            $table->string('description');
            $table->foreignId('erp_product_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('car_model_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specific_shortage_items');
    }
};
