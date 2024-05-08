<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->timestamp('delivered_customer_at')->nullable();
            $table->foreignId('current_location')->index()->nullable()->constrained('locations');
            $table->json('files')->nullable();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign('vehicles_current_location_foreign');
            $table->dropColumn('delivered_customer_at');
            $table->dropColumn('current_location');
            $table->dropColumn('files');
            $table->dropColumn('delivered_customer_at');
        });
    }
};
