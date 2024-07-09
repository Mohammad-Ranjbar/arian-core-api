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
        Schema::table('sale_companies', function (Blueprint $table) {
            $table->string('invitation')->nullable();
            $table->string('contract')->nullable();
            $table->string('erp_contract_url')->nullable();
            $table->string('sync_order_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_companies', function (Blueprint $table) {
            $table->dropColumn(['invitation','contract']);
        });
    }
};
