<?php

use App\Models\StopReason;
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
        Schema::table('stop_reasons', function (Blueprint $table) {
            $table->string('has_options')->nullable()->change();
        });
        StopReason::query()->where('has_options' , 1)->update(['has_options' => 'product-items']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stop_reasons', function (Blueprint $table) {
            //
        });
    }
};
