<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('qc_codes', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->enum('item_type',['vehicle','body']);
            $table->enum('type',[0,1]);
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('qc_codes');
    }
};
