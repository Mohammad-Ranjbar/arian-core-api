<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('cabin_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->index()->constrained()->cascadeOnDelete();
            $table->string('serial_1_chr')->index();
            $table->string('serial_2_chr')->index();
            $table->string('color_chr')->index();
            $table->string('year_chr')->index();
            $table->string('body_option_chr')->index();
            $table->integer('counter')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('used')->index()->default(false);
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('cabin_numbers');
    }
};
