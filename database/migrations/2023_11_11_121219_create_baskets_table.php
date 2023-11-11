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
        Schema::create('baskets', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->string('status')->default('new');
            $table->softDeletes();
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('baskets');
    }
};
