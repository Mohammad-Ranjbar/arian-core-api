<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('year_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('year')->unique();
            $table->string('chr');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('year_maps');
    }
};
