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

            $table->unsignedBigInteger('erp_product_item_id');
            $table->unsignedBigInteger('production_line_activity_id');
           $table->foreign('erp_product_item_id','erp_foreign')->references('id')
               ->on('erp_product_items')->cascadeOnDelete();
            $table->foreign('production_line_activity_id','production_line_foreign')->references('id')
                ->on('production_line_activities')->cascadeOnDelete();

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
