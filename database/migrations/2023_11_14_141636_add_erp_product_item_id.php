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
        Schema::table('barcodes', function (Blueprint $table) {
            $table->foreignId('erp_product_item_id')->nullable()->index();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropIndex('barcodes_erp_product_item_id_index');
            $table->dropColumn('erp_product_item_id');
        });
    }
};
