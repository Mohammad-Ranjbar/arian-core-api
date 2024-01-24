<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('station_user', function (Blueprint $table) {
            $table->json('data')->nullable();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('station_user', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
