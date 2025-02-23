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
        Schema::table('shortage_items', function (Blueprint $table) {
            $table->foreignId('shortage_item_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shortage_items', function (Blueprint $table) {
            $table->dropForeign('shortage_items_shortage_item_id_foreign');
        });
    }
};
