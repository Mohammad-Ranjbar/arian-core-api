<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('bill_of_ladings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('transit_source_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('transit_destination_id')->index()->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('request_no')->nullable();
            $table->string('status')->default('new');
            $table->integer('priority')->nullable();
            $table->string('cottage')->nullable();
            $table->string('weight')->nullable();
            $table->string('batch')->nullable();
            $table->string('type')->default('container');
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }


    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_ladings');
    }
};
