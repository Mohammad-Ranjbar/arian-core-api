<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->string('production_order_number')->unique();
            $table->string('engine_no');
            $table->string('cabin_no');
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('make_code_id')->constrained()->cascadeOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
