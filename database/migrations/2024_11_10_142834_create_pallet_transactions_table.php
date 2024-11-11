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
        Schema::create('pallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pallet_id')->constrained('pallets')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->BigInteger('qty');
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pallet_transactions');
    }
};
