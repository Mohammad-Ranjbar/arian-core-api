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
        Schema::table('production_checklists', function (Blueprint $table) {
            $table->uuid('file_id')->nullable()->after('title');
        });

        Schema::table('item_input_checklists', function (Blueprint $table) {
            $table->uuid('file_id')->nullable()->after('title');
        });

        Schema::table('cop_plans', function (Blueprint $table) {
            $table->uuid('file_id')->nullable()->after('title');
        });

        Schema::table('item_technical_checklists', function (Blueprint $table) {
            $table->uuid('file_id')->nullable()->after('title');
        });

        Schema::table('product_boms', function (Blueprint $table) {
            $table->uuid('file_id')->nullable()->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_boms', function (Blueprint $table) {
            $table->dropColumn('file_id');
        });

        Schema::table('item_technical_checklists', function (Blueprint $table) {
            $table->dropColumn('file_id');
        });

        Schema::table('cop_plans', function (Blueprint $table) {
            $table->dropColumn('file_id');
        });

        Schema::table('item_input_checklists', function (Blueprint $table) {
            $table->dropColumn('file_id');
        });

        Schema::table('production_checklists', function (Blueprint $table) {
            $table->dropColumn('file_id');
        });
    }
};
