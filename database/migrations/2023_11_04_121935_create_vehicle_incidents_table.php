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
        Schema::create('vehicle_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('intensity');
            $table->string('method');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->string('vin')->index();
            $table->foreignId('production_tracking_id')->index()->nullable()->constrained('production_tracking')->cascadeOnDelete();
            $table->foreignId('element_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->index()->constrained()->cascadeOnDelete();
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('checked_at')->default(now());
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_incidents');
    }
};
