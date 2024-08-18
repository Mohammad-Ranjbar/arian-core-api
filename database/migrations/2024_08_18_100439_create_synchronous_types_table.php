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
        Schema::create('synchronous_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('production_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('qty');
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('synchronous_types');
    }
};
