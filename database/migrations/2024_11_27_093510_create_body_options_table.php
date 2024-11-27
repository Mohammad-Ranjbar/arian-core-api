<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('body_options', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('body_options');
    }
};
