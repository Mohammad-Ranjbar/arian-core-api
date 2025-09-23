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
        Schema::create('p_d_i_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vin')->index();
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete();
            $table->string('pdi_result');
            $table->string('pdi_result_description',250);
            $table->string('pdi_result_description_2',100)->nullable();
            $table->string('model_year')->nullable();
            $table->string('engine_no')->nullable();
            $table->string('police_inspection_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p_d_i_s');
    }
};
