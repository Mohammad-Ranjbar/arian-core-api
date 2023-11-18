<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('gate_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->index()->constrained()->cascadeOnDelete();
            $table->string('status')->default('new');
            $table->string('type');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('gate_permits');
    }
};
