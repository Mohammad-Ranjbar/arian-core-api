<?php

namespace App\Http\Controllers;

use App\Http\Resources\MainItemShortageResource;
use App\Models\MainItemShortage;
use App\Repositories\MainItemShortageRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class MainItemShortageController extends CrudController
{
    public function __construct(public readonly MainItemShortageRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(MainItemShortageResource::class);
    }

    /**
     * Freeze a draft. From here on, a related item change forks a new draft
     * instead of touching this record.
     */
    public function activate(MainItemShortage $mainItemShortage)
    {
        if (! $mainItemShortage->isDraft()) {
            return $this->responseError('این نسخه قبلا فعال شده است.');
        }

        DB::transaction(function () use ($mainItemShortage): void {
            MainItemShortage::query()
                ->where('item_no', $mainItemShortage->getAttribute('item_no'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->each->update(['is_active' => false]);

            $mainItemShortage->update(['is_active' => true]);
        });

        return MainItemShortageResource::make($mainItemShortage->load([
            'relatedItems.actionLogs.user.stations',
            'actionLogs.user.stations',
        ]));
    }

    /**
     * @throws Throwable
     */
    public function destroy($id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id): JsonResponse {
                $mainItemShortage = MainItemShortage::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($mainItemShortage->registerShortages()->exists()) {
                    return $this->usedInShortageResponse();
                }

                if ($mainItemShortage->delete()) {
                    return response()->json(['message' => 'ایتم با موفقیت حذف شد']);
                }

                return $this->responseError('امکان حذف آیتم مورد نظر وجود ندارد.');
            });
        } catch (QueryException $exception) {
            if ($this->isRegisterShortageForeignKeyViolation($exception)) {
                return $this->usedInShortageResponse();
            }

            throw $exception;
        }
    }

    private function usedInShortageResponse(): JsonResponse
    {
        return $this->responseError('این مورد در کسری استفاده شده و امکان حذف آن وجود ندارد.');
    }

    private function isRegisterShortageForeignKeyViolation(QueryException $exception): bool
    {
        if (($exception->errorInfo[0] ?? null) !== '23000') {
            return false;
        }

        if ((int) ($exception->errorInfo[1] ?? 0) === 1451) {
            return str_contains(
                $exception->getMessage(),
                'register_shortages_main_item_shortage_id_foreign'
            );
        }

        return (int) ($exception->errorInfo[1] ?? 0) === 19
            && str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')
            && str_contains($exception->getSql(), 'main_item_shortages');
    }
}
