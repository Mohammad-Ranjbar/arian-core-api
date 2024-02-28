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
        Schema::table('gate_permits', function (Blueprint $table) {
            $table->enum('exit_type', [1, 2])->nullable();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('gate_permits', function (Blueprint $table) {
            $table->dropColumn('exit_type');
        });
    }
};
