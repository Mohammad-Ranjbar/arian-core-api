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
        Schema::create('item_input_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('car_model_item_input_checklist', function (Blueprint $table) {
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_input_checklist_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_model_item_input_checklist');
        Schema::dropIfExists('item_input_checklists');
    }
};
