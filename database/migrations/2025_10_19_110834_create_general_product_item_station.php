<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('general_product_item_station', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->restrictOnDelete();
            $table->foreignId('general_product_item_id')->constrained()->restrictOnDelete();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('general_product_item_station');
    }
};
