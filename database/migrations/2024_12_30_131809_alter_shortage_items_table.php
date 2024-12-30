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
        Schema::table('shortage_items', function (Blueprint $table) {
            $table->string('routing_link_code')->index()->nullable();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('shortage_items', function (Blueprint $table) {
            $table->dropColumn('routing_link_code');
        });
    }
};
