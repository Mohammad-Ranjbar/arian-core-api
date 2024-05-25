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
        Schema::table('production_lines', function (Blueprint $table) {
            $table->boolean('show_in_board')->default(true);
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->dropColumn('show_in_board');
        });
    }
};
