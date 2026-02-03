<?php

namespace App\Models;

use App\Services\ResponseTimeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ErpJob extends Model
{
    use Prunable;

    const string QUEUE_UNIQUE = 'unique';

    const string QUEUE_ = 'parallel';

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
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function setExecutionTime($startTime, $endTime): void
    {
        $executeTime = ResponseTimeService::getResponseTime($startTime, $endTime);

        $this->update([
            'execute_time' => $executeTime
        ]);
    }

    public function setStatusInProgress(): bool
    {
        return $this->update([
            'status' => 'in-progress'
        ]);
    }

    public function setStatusStart(): bool
    {
        return $this->update([
            'status' => 'start'
        ]);
    }


    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }

    public function setStatusSuccess($response): bool
    {
        return $this->update([
            'status' => 'success',
            'description' => json_encode($response)
        ]);
    }

    public function setStatusFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'description' => $message
        ]);
    }

    public function setEmptyDescription(): void
    {
        $this->update([
            'description' => ''
        ]);
    }

    public function incrementTries(): void
    {
        $this->increment('tries');
    }

    protected function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subDays(5));
    }

}
