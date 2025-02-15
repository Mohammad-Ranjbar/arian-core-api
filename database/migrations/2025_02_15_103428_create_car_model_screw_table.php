<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('car_model_screw', function (Blueprint $table) {
           $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
           $table->foreignId('screw_id')->constrained()->cascadeOnDelete();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('car_model_screw');
    }
};
