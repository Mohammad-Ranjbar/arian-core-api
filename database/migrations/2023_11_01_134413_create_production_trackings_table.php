<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->index();
            $table->string('type');
            $table->morphs('model');
            $table->unsignedBigInteger('mode_id');
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_tracking');
    }
};
