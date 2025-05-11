<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('block_items', function (Blueprint $table) {
            $table->enum('type',['user','system'])->default('user');
        });
    }


    public function down(): void
    {
        Schema::table('block_items', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
