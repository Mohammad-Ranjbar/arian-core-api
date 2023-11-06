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
        Schema::create('transit_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('national_code', 10)->index()->unique();
            $table->string('phone')->unique();
            $table->string('bank_information')->nullable();
            $table->json('plate');
            $table->string('car');
            $table->foreignId('transit_company_id')->index()->constrained()->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transit_drivers');
    }
};
