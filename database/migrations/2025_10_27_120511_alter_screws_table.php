<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('screws', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->foreignId('torque_measuring_equipment_id')->nullable()->constrained('torque_measuring_equipment')->cascadeOnDelete();
                $table->foreignId('salon_id')->nullable()->constrained()->cascadeOnDelete();
            });

        });
    }

    public function down(): void
    {
        Schema::table('screws', function (Blueprint $table) {
            $table->dropForeign(['torque_measuring_equipment_id']);
            $table->dropForeign(['salon_id']);
            $table->dropColumn('torque_measuring_equipment_id');
            $table->dropColumn('salon_id');
        });
    }
};
