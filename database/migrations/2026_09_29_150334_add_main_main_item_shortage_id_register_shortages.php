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
        Schema::table('register_shortages', function (Blueprint $table) {
            $table->foreignId('main_item_shortage_id')->after('id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('register_shortages', function (Blueprint $table) {
            $table->dropForeign(['main_item_shortage_id']);
            $table->dropColumn('main_item_shortage_id');
        });
    }
};
