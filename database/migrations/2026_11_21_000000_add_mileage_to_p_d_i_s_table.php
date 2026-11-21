<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('p_d_i_s', function (Blueprint $table) {
            $table->string('mileage')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('p_d_i_s', function (Blueprint $table) {
            $table->dropColumn('mileage');
        });
    }
};
