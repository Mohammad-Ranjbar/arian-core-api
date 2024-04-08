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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->text('grade_description')->nullable();
            $table->text('qc_description')->nullable();

        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('grade_description');
            $table->dropColumn('qc_description');
        });
    }
};
