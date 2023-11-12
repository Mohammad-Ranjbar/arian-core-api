<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('grade')->nullable();
            $table->string('qc_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('grade');
            $table->dropColumn('qc_status');
        });
    }
};
