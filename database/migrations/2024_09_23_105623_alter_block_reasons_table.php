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
        Schema::table('block_reasons', function (Blueprint $table) {
            $table->string('block_type')->nullable();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('block_reasons', function (Blueprint $table) {
            $table->dropColumn('block_type');
        });
    }
};
