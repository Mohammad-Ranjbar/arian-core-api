<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('main_item_shortages', function (Blueprint $table) {
            $table->id();
            $table->string('location_code');
            $table->foreignId('car_model_id')->constrained()->restrictOnDelete();
            $table->string('car_model_reference')->index();
            $table->string('item_no');
            $table->text('description');//description of item
            $table->unsignedTinyInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('main_item_shortage_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('main_item_shortages');
    }
};
