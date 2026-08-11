<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alternative_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alternative_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('erp_product_item_id')->constrained()->restrictOnDelete();
            $table->string('item_no');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('packing_qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternative_items');
    }
};
