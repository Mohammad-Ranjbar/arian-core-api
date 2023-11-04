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
        Schema::create('incident_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('element_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->index()->constrained()->cascadeOnDelete();
            $table->nullableMorphs('model');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_sources');
    }
};
