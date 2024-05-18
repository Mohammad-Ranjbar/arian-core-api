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
        Schema::table('gate_permits', function (Blueprint $table) {
            $table->foreignId('location_id')->after('type')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gate_permits', function (Blueprint $table) {
            $table->dropForeign('gate_permits_location_id_foreign');
            $table->dropColumn('location_id');
        });
    }
};
