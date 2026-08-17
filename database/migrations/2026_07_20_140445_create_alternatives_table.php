<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alternatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alternative_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('item_no');
            $table->string('document_number')->nullable();//before send to ERP in null
            $table->unsignedInteger('counter')->default(0);
            $table->string('production_order_number');
            $table->enum('status', ['create','send','failed','success'])->index()->default('create');
            $table->timestamp('send_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('success_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->jsonb('data')->nullable();//result from ERP
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternatives');
    }
};
