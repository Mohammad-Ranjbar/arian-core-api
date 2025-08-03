<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('car_model_color_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->string('color_code');
            $table->string('variant');
            $table->unique(['variant', 'color_code']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_model_color_codes');
    }
};
