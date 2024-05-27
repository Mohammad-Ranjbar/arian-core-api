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

        Schema::dropIfExists('sale_companies');
        Schema::create('sale_company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained();
            $table->foreignId('sale_company_id')->index()->constrained();
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
