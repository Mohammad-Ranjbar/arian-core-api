<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('general_product_items', function (Blueprint $table) {
            $table->integer('priority')->default(0);
            $table->integer('height')->default(1);
        });
    }


    public function down(): void
    {
        Schema::table('general_product_items', function (Blueprint $table) {
            $table->dropColumn('priority');
            $table->dropColumn('height');
        });
    }
};
