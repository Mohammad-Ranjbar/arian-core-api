<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RestErpJob extends Model
{
    protected $fillable = [
        'uuid', 'model_type', 'model_id', 'user_id', 'section', 'service', 'server',
        'endpoint', 'method', 'url', 'execution_mode', 'connection', 'queue', 'status',
        'tries', 'execute_time', 'parameters', 'payload_hash', 'request_payload',
        'request_headers', 'request_context', 'client_ip', 'peer_ip', 'user_agent',
        'client_computer_name', 'response', 'response_headers', 'response_context',
        'http_status', 'provider_http_status', 'error_code', 'description', 'queued_at',
        'dispatched_at', 'started_at', 'completed_at', 'failed_at', 'last_retry_at', 'last_retry_user_id', 'ignored_at',
    ];

    protected $hidden = ['parameters'];

    protected $casts = [
        'parameters' => 'encrypted:array',
        'response' => 'encrypted:array',
        'request_payload' => 'array',
        'request_headers' => 'array',
        'request_context' => 'array',
        'response_headers' => 'array',
        'response_context' => 'array',
        'tries' => 'integer',
        'http_status' => 'integer',
        'provider_http_status' => 'integer',
        'execute_time' => 'float',
        'queued_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'ignored_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }
}
