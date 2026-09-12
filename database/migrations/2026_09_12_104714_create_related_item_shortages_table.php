<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('related_item_shortages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_item_shortage_id')->constrained()->cascadeOnDelete();
            $table->string('item_no');
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('related_item_shortages');
    }
};
