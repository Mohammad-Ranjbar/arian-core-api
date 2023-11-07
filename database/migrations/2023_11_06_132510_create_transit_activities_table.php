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
        Schema::create('transit_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('transit_source_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('transit_destination_id')->index()->constrained()->cascadeOnDelete();
            $table->string('bill_of_lading');
            $table->string('request_no');
            $table->string('status')->default('new');
            $table->integer('priority')->nullable();
            $table->string('container')->unique();
            $table->string('cottage');
            $table->string('weight');
            $table->string('batch');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('exit_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('transit_activities');
    }
};
