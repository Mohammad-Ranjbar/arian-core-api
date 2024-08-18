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
        Schema::create('synchronous_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vin')->constrained('vehicles','vin')->cascadeOnDelete();
            $table->foreignId('synchronous_id')->constrained('synchronous')->cascadeOnDelete();
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('synchronous_items');
    }
};
