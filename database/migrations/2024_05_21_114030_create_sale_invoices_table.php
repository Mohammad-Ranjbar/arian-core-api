<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('sale_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_company_id')->index()->constrained();
            $table->string('reference')->index()->unique();
            $table->string('online_order_id')->index()->unique()->nullable();
            $table->string('payment_status')->index()->default('pending');
            $table->string('erp_status')->index()->default('pending');
            $table->json('data')->nullable();
            $table->string('type')->index();
            $table->integer('value');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('sale_invoices');
    }
};
