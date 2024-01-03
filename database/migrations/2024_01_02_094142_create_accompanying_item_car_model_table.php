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
        Schema::dropIfExists('accompanying_item_make_code');
        Schema::create('accompanying_item_car_model', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accompanying_item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('accompanying_item_car_model');
    }
};
