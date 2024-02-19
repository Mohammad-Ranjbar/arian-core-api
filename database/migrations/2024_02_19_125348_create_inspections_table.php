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
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->index();
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->softDeletes();
            $table->timestamps();
        });
    }
    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
