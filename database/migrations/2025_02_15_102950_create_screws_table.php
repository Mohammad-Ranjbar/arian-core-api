<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('screws', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->float('min');
            $table->float('max');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screws');
    }
};
