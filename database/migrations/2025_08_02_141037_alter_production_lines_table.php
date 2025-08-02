<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->enum('production_line_type', ['vehicle','engine'])->after('id')->default('vehicle')->index();
            $table->string('printer_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('production_lines', function (Blueprint $table) {
            $table->dropColumn('production_line_type');
            $table->dropColumn('printer_address');
        });
    }
};
