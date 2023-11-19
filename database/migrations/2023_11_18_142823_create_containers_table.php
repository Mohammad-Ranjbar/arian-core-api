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
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->boolean('on_shipping')->default(false);
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('containers');
    }
};
