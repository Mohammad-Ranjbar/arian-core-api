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
        Schema::create('erp_service_item_production_line_activity', function (Blueprint $table) {

            $table->foreignId('production_line_activity_id')
                ->constrained('production_line_activities','id','indexName1')->cascadeOnDelete();

            $table->foreignId('erp_service_item_id')->constrained('erp_service_items','id','IndexName2')->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_service_item_production_line_activity');
    }
};
