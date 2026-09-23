<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alternative_items', function (Blueprint $table) {
            $table->decimal('packing_qty', 10, 2)->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alternative_items', function (Blueprint $table) {
            $table->unsignedBigInteger('packing_qty')->change();
        });
    }
};
