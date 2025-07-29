<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('pdi_check_lists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->uuid('file_id')->nullable();
            $table->timestamps();
        });

        Schema::create('car_model_pdi_check_list', function (Blueprint $table) {
            $table->foreignId('pdi_check_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_model_pdi_check_list');
        Schema::dropIfExists('pdi_check_lists');
    }
};
