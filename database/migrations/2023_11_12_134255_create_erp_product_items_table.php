<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('erp_product_items', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('tracking_code')->nullable();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('type')->nullable();

            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_product_items');
    }
};
