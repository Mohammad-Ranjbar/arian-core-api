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
        Schema::create('alternative_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alternative_id')->constrained()->restrictOnDelete();
            $table->string('variant_code')->nullable();
            $table->unsignedBigInteger('quantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alternative_variants');
    }
};
