<?php

namespace App\Models;

use App\Services\ResponseTimeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ErpJob extends Model
{

    const string QUEUE_UNIQUE = 'unique';
    const string QUEUE_PARALEL  = 'paralel';

    const string BLOCK_ITEM = 'block-item';

    protected $fillable = [
        'model_id',
        'model_type',
        'status',
        'section',
        'description',
        'user_id',
        'method',
        'url',
        'parameters',
        'execute_time',
        'tries',
        'queue'
    ];

    protected $casts = [
        'parameters' => 'array',

    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function setExecutionTime($startTime,$endTime): void
    {
       $executeTime =  ResponseTimeService::getResponseTime($startTime,$endTime);

       $this->update([
           'execute_time' => $executeTime
       ]);
    }

    public function setStatusSuccess(): bool
    {
        return $this->update([
            'status' => 'success'
        ]);
    }

    public function setStatusFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'description' => $message
        ]);
    }

    public function incrementTries(): void
    {
        $this->increment('tries');
    }

}
