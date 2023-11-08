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
        Schema::create('production_line_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_activity_id');
            $table->foreign('production_line_activity_id', 'activity_id_index')
                ->references('id')
                ->on('production_line_activities')
                ->cascadeOnDelete();
            $table->string('action')->index();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('production_line_activity_logs');
    }
};
