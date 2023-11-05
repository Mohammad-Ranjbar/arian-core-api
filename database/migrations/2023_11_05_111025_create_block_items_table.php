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
        Schema::create('block_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blocked_by');
            $table->unsignedBigInteger('unblocked_by')->nullable();
            $table->foreignId('block_reason_id')->index()->constrained()->cascadeOnDelete();
            $table->timestamp('unblocked_at')->nullable();
            $table->text('description')->nullable();
            $table->string('vin')->index();
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('block_items');
    }
};
