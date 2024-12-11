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
        Schema::table('bill_of_lading_item', function (Blueprint $table) {
            $table->string('type')->nullable();
        });

        \App\Models\BillOfLadingItem::query()->where('model_type',\App\Models\Container::class)->update(['type' => 'containers']);
        \App\Models\BillOfLadingItem::query()->where('model_type',\App\Models\Vehicle::class)->update(['type' => 'vehicles']);
        \App\Models\BillOfLadingItem::query()->where('model_type',\App\Models\Vehicle::class)
            ->whereHas('billOfLading',function ($query){
                $query->where('location_destination_id',4)->orWhere('location_source_id',4);
            })
            ->update(['type' => 'bodies']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_of_lading_item', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
