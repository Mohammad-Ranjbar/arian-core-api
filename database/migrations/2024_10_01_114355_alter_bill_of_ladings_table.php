<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
//            $table->dropUnique('bill_of_ladings_code_unique');
            $table->unique(['code', 'location_source_id', 'location_destination_id'], 'unique_code_location_src_dest');
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            //
        });
    }
};
