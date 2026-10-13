<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rest_erp_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depends_on_id')->nullable()->constrained('rest_erp_jobs')->restrictOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('model_type')->nullable();
            $table->string('model_id')->nullable();
            $table->index(['model_type', 'model_id']);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('section')->index();
            $table->string('service');
            $table->string('server')->default('pre_go_live');
            $table->string('endpoint')->index();
            $table->string('method', 10)->default('POST');
            $table->text('url');
            $table->string('execution_mode', 10);
            $table->string('connection')->nullable();
            $table->string('queue')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedInteger('tries')->default(0);
            $table->decimal('execute_time', 12, 4)->default(0);
            $table->longText('parameters');
            $table->char('payload_hash', 64);
            $table->json('request_payload');
            $table->json('request_context');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedSmallInteger('provider_http_status')->nullable();
            $table->string('error_code')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('last_retry_at')->nullable();
            $table->foreignId('last_retry_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ignored_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['status', 'dispatched_at', 'queued_at']);
        });

        // Preserve audit records: referenced actors must be soft-deleted rather than hard-deleted.
        Schema::create('rest_erp_job_actors', function (Blueprint $table) {
            $table->foreignId('rest_erp_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->primary(['rest_erp_job_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rest_erp_job_actors');
        // Clear self-references before dropping the table, including on SQLite.
        if (Schema::hasColumn('rest_erp_jobs', 'depends_on_id')) {
            DB::table('rest_erp_jobs')->whereNotNull('depends_on_id')->update(['depends_on_id' => null]);
        }
        Schema::dropIfExists('rest_erp_jobs');
    }
};
