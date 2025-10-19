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
        Schema::table('general_product_items', function (Blueprint $table) {
            $table->after('id',function (Blueprint $table){
                $table->enum('part_type',['kd','local'])->nullable()->index();
                $table->boolean('label_status')->nullable()->index();
                $table->boolean('hack_status')->nullable()->index();
                $table->enum('count_lable',[1,2])->nullable()->index();
            });
        });
    }


    public function down(): void
    {
        Schema::table('general_product_items', function (Blueprint $table) {
            $table->dropColumn([
                'part_type',
                'label_status',
                'hack_status',
                'count_lable'
            ]);
        });
    }
};
