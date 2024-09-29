<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('general_product_items', function (Blueprint $table) {
           if (Schema::hasColumn('code')){
               $table->dropColumn('code');
           }
        });
    }


    public function down(): void
    {
        Schema::table('general_product_items', function (Blueprint $table) {
            //
        });
    }
};
