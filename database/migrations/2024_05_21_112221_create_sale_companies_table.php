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
        Schema::create('sale_companies', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('url');
            $table->text('token')->nullable();
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_companies');
    }
};
