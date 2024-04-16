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
        Schema::table('action_logs', function (Blueprint $table) {
            $table->string('model_id')->change();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('action_logs', function (Blueprint $table) {

        });
    }
};
