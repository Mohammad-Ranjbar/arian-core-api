<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('synchronous_type_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('synchronous_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('erp_product_item_id')->constrained()->cascadeOnDelete();
            $table->string('cross_reference');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synchronous_items');
    }
};
