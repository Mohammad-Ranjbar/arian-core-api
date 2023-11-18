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
        Schema::create('bill_of_lading_item', function (Blueprint $table) {
            $table->foreignId('bill_of_lading_id')->index()->constrained();
            $table->morphs('model');
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_lading_item');
    }
};
