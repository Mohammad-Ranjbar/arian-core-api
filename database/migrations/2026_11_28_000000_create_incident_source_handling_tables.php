<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_source_handlings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_source_id');
            $table->foreign('incident_source_id', 'is_handling_source_fk')
                ->references('id')->on('incident_sources')->cascadeOnDelete();
            $table->foreignId('incident_source_selection_id')->nullable()->unique();
            $table->foreign('incident_source_selection_id', 'is_handling_selection_fk')
                ->references('id')->on('incident_source_selections')->nullOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('selection_created_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('incident_source_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_source_handling_id')->unique();
            $table->foreign('incident_source_handling_id', 'is_action_handling_fk')
                ->references('id')->on('incident_source_handlings')->cascadeOnDelete();
            $table->text('description');
            $table->foreignId('user_id')->nullable();
            $table->foreign('user_id', 'is_action_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('incident_source_handling_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_source_handling_id');
            $table->foreign('incident_source_handling_id', 'is_event_handling_fk')
                ->references('id')->on('incident_source_handlings')->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('user_id')->nullable();
            $table->foreign('user_id', 'is_event_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_source_handling_events');
        Schema::dropIfExists('incident_source_actions');
        Schema::dropIfExists('incident_source_handlings');
    }
};
