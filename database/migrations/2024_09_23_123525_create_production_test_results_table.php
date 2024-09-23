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
        Schema::create('production_test_results', function (Blueprint $table) {
            $table->id();
            $table->string('vin');
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete();
            $table->json('files');
            $table->text('description');
            $table->foreignId('production_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('production_test_results');
    }
};
