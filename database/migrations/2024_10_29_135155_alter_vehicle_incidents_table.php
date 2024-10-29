<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            $table->foreignId('incident_category_id')->index()->nullable()->constrained('incident_categories')->cascadeOnDelete();
        });
    }


    public function down(): void
    {
        Schema::table('vehicle_incidents', function (Blueprint $table) {
            $table->dropForeign('vehicle_incidents_incident_category_id_foreign');
        });
    }
};
