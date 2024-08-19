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
        Schema::create('stop_fact_user', function (Blueprint $table) {
           $table->foreignId('stop_fact_id')->constrained()->cascadeOnDelete();
           $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('stop_fact_user');
    }
};
