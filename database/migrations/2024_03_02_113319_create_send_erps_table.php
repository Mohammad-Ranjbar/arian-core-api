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
        Schema::create('send_erps', function (Blueprint $table) {
            $table->id();
            $table->boolean('on_enter')->nullable();
            $table->boolean('on_exit')->nullable();
            $table->morphs('model');
            $table->timestamps();
        });
    }
    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('send_erps');
    }
};
