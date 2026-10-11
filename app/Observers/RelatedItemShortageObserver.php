<?php

namespace App\Observers;

use App\Models\ActionLog;
use App\Models\RelatedItemShortage;
use Illuminate\Support\Facades\Auth;
use LogicException;

class RelatedItemShortageObserver
{
    public function created(RelatedItemShortage $relatedItemShortage): void
    {
        $this->record(
            $relatedItemShortage,
            ActionLog::RELATED_ITEM_SHORTAGE_CREATED,
            null,
            $relatedItemShortage->getAttributes(),
        );
    }

    public function updated(RelatedItemShortage $relatedItemShortage): void
    {
        if (! $relatedItemShortage->wasChanged()) {
            return;
        }

        $this->record(
            $relatedItemShortage,
            ActionLog::RELATED_ITEM_SHORTAGE_UPDATED,
            $relatedItemShortage->getRawOriginal(),
            $relatedItemShortage->getAttributes(),
        );
    }

    public function deleted(RelatedItemShortage $relatedItemShortage): void
    {
        $this->record(
            $relatedItemShortage,
            ActionLog::RELATED_ITEM_SHORTAGE_DELETED,
            $relatedItemShortage->getAttributes(),
            null,
        );
    }

    private function record(
        RelatedItemShortage $relatedItemShortage,
        string $action,
        ?array $before,
        ?array $after,
    ): void {
        $userId = Auth::id();

        if ($userId === null) {
            throw new LogicException('ثبت ممیزی آیتم وابسته کسری بدون کاربر امکان‌پذیر نیست.');
        }

        $relatedItemShortage->actionLogs()->create([
            'user_id' => $userId,
            'action' => $action,
            'data' => [
                'before' => $before,
                'after' => $after,
            ],
        ]);
    }
}
