<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            $table->string('production_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            $table->dropColumn('production_type');
        });
    }
};
