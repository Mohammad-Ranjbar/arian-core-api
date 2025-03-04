<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('synchronous_items', function (Blueprint $table) {
            $table->foreignId('erp_product_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('cross_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('synchronous_items', function (Blueprint $table) {
            $table->dropColumn('erp_product_item_id');
            $table->dropColumn('cross_reference');
        });
    }
};
