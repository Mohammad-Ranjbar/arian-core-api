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
        Schema::create('erp_product_item_production_line_activity', function (Blueprint $table) {
           $table->foreignId('erp_product_item_id')->constrained()->cascadeOnDelete();
           $table->foreignId('production_line_activity_id')->constrained()->cascadeOnDelete();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_product_item_production_line_activity');
    }
};
