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
        Schema::table('production_line_activities', function (Blueprint $table) {
            $table->boolean('notify_on_stop')->nullable();
            $table->boolean('notify_on_start')->nullable();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('production_line_activities', function (Blueprint $table) {
            $table->dropColumn('notify_on_stop');
            $table->dropColumn('notify_on_start');
        });
    }
};
