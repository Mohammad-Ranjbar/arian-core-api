<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('car_model_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('code');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_model_locations');
    }
};
