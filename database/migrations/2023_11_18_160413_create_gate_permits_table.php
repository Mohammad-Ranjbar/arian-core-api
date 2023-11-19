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
        Schema::create('gate_permits', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('type');
            $table->morphs('owner');
            $table->softDeletes();
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_permits');
    }
};
