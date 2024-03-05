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
        if (Schema::hasColumn('car_models', 'production_line_id')) {
            Schema::table('car_models', function (Blueprint $table) {
                $table->dropConstrainedForeignId('production_line_id');
            });
        }
        Schema::create('car_model_production_line', function (Blueprint $table) {
            $table->foreignId('car_model_id')->index()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('production_line_id')->index()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('car_model_production_line');
    }
};
