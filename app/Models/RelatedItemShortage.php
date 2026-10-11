<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use LogicException;

class RelatedItemShortage extends Model
{
    protected $fillable = [
        'main_item_shortage_id',
        'item_no',
        'description',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function mainItemShortage(): BelongsTo
    {
        return $this->belongsTo(MainItemShortage::class);
    }

    public function actionLogs(): MorphMany
    {
        return $this->morphMany(ActionLog::class, 'model');
    }

    public function recordRemovalFromFork(MainItemShortage $fork): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            throw new LogicException('ثبت ممیزی آیتم وابسته کسری بدون کاربر امکان‌پذیر نیست.');
        }

        $this->actionLogs()->create([
            'user_id' => $userId,
            'action' => ActionLog::RELATED_ITEM_SHORTAGE_DELETED,
            'data' => [
                'before' => $this->getAttributes(),
                'after' => null,
                'forked_main_item_shortage_id' => $fork->getKey(),
            ],
        ]);
    }
}
