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
        Schema::table('production_tracking', function (Blueprint $table) {
            $table->json('data')->after('id')->nullable();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('production_tracking', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
