<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('erp_jobs', function (Blueprint $table) {
            $table->id();
            $table->morphs('model','modelIndex');
            $table->string('status')->index()->default('start');
            $table->string('section')->index();
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->string('queue')->default('default');
            $table->text('url');
            $table->integer('tries')->default(0);
            $table->float('execute_time',10,4)->default(0);
            $table->json('parameters')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_jobs');
    }
};
