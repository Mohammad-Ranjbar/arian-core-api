<?php

namespace App\Observers;

use App\Models\ActionLog;
use App\Models\MainItemShortage;
use Illuminate\Support\Facades\Auth;
use LogicException;

class MainItemShortageObserver
{
    public function created(MainItemShortage $mainItemShortage): void
    {
        if ($mainItemShortage->getAttribute('main_item_shortage_id') === null) {
            $this->record(
                $mainItemShortage,
                ActionLog::MAIN_ITEM_SHORTAGE_CREATED,
                null,
                $mainItemShortage->getAttributes(),
            );

            return;
        }

        $source = $mainItemShortage->mainItemShortage()->firstOrFail();

        $this->record(
            $mainItemShortage,
            ActionLog::MAIN_ITEM_SHORTAGE_FORKED,
            $source->getAttributes(),
            $mainItemShortage->getAttributes(),
        );
    }

    public function updated(MainItemShortage $mainItemShortage): void
    {
        if (! $mainItemShortage->wasChanged()) {
            return;
        }

        $this->record(
            $mainItemShortage,
            ActionLog::MAIN_ITEM_SHORTAGE_UPDATED,
            $mainItemShortage->getRawOriginal(),
            $mainItemShortage->getAttributes(),
        );
    }

    public function deleted(MainItemShortage $mainItemShortage): void
    {
        $this->record(
            $mainItemShortage,
            ActionLog::MAIN_ITEM_SHORTAGE_DELETED,
            $mainItemShortage->getAttributes(),
            null,
        );
    }

    private function record(
        MainItemShortage $mainItemShortage,
        string $action,
        ?array $before,
        ?array $after,
    ): void {
        $userId = Auth::id();

        if ($userId === null) {
            throw new LogicException('ثبت ممیزی آیتم اصلی کسری بدون کاربر امکان‌پذیر نیست.');
        }

        $mainItemShortage->actionLogs()->create([
            'user_id' => $userId,
            'action' => $action,
            'data' => [
                'before' => $before,
                'after' => $after,
            ],
        ]);
    }
}
