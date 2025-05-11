<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('torque_coefficients', function (Blueprint $table) {
            $table->id();
            $table->float('low_1')->default(0);
            $table->float('low_2')->default(0);
            $table->float('low_3')->default(0);
            $table->float('high_1')->default(0);
            $table->float('high_2')->default(0);
            $table->float('high_3')->default(0);
            $table->float('high_4')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('torque_coefficients');
    }
};
