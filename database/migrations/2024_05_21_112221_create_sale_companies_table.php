<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sale_companies');
        Schema::enableForeignKeyConstraints();
        Schema::create('sale_companies', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('folder_name');
            $table->string('erp_url');
            $table->string('site_url');
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sale_companies');
        Schema::enableForeignKeyConstraints();
    }
};
