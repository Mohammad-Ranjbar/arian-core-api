<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('register_shortages', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamp('resolved_at')->nullable();
            });
        });
    }

    public function down(): void
    {
        Schema::table('register_shortages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn('resolved_at');
        });
    }
};
