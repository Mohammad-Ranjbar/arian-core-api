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
        Schema::table('transit_activities', function (Blueprint $table) {
            $table->foreignId('transit_driver_id')->index()->constrained('transit_drivers','id')->cascadeOnDelete();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('transit_activities', function (Blueprint $table) {
            $table->dropColumn('transit_driver_id');
        });
    }
};
