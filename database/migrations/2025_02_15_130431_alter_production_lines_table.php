<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->string('location')->nullable();
            $table->string('central_location')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->dropColumn('location');
            $table->dropColumn('central_location');
        });
    }
};
