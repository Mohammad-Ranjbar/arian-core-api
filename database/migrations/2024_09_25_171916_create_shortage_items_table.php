<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('shortage_items', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->index();
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('erp_product_item_id')->references('id')->on('erp_product_items')->cascadeOnDelete();
            $table->string('status')->index()->default('pending');
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('shortage_items');
    }
};
