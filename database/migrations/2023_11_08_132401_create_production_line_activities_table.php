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
        Schema::create('production_line_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_line_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('stop_reason_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('stop_type_id')->index()->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('stop_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('production_line_activities');
    }
};
