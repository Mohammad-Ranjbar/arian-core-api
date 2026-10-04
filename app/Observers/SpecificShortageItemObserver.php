<?php

namespace App\Observers;

use App\Models\ActionLog;
use App\Models\SpecificShortageItem;
use Illuminate\Support\Facades\Auth;
use LogicException;

class SpecificShortageItemObserver
{
    public function created(SpecificShortageItem $specificShortageItem): void
    {
        $this->record(
            $specificShortageItem,
            ActionLog::SPECIFIC_SHORTAGE_ITEM_CREATED,
            null,
            $specificShortageItem->getAttributes(),
        );
    }

    public function updated(SpecificShortageItem $specificShortageItem): void
    {
        if (! $specificShortageItem->wasChanged('is_active')) {
            return;
        }

        $action = $specificShortageItem->getAttribute('is_active')
            ? ActionLog::SPECIFIC_SHORTAGE_ITEM_ACTIVATED
            : ActionLog::SPECIFIC_SHORTAGE_ITEM_DEACTIVATED;

        $this->record(
            $specificShortageItem,
            $action,
            $specificShortageItem->getRawOriginal(),
            $specificShortageItem->getAttributes(),
        );
    }

    public function deleted(SpecificShortageItem $specificShortageItem): void
    {
        $this->record(
            $specificShortageItem,
            ActionLog::SPECIFIC_SHORTAGE_ITEM_DELETED,
            $specificShortageItem->getAttributes(),
            null,
        );
    }

    private function record(
        SpecificShortageItem $specificShortageItem,
        string $action,
        ?array $before,
        ?array $after,
    ): void {
        $userId = Auth::id();

        if ($userId === null) {
            throw new LogicException('ثبت ممیزی آیتم کسری خاص بدون کاربر امکان‌پذیر نیست.');
        }

        $specificShortageItem->actionLogs()->create([
            'user_id' => $userId,
            'action' => $action,
            'data' => [
                'before' => $before,
                'after' => $after,
            ],
        ]);
    }
}
