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
        Schema::table('synchronous_types', function (Blueprint $table) {
            $table->string('printer_ip');
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('synchronous_types', function (Blueprint $table) {
            $table->dropColumn('printer_ip');
        });
    }
};
