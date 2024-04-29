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
        Schema::table('stop_reasons', function (Blueprint $table) {
            $table->dropUnique('stop_reasons_title_unique');
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('stop_reasons', function (Blueprint $table) {
            //
        });
    }
};
