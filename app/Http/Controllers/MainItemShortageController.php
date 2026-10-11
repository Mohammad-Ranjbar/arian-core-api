<?php

namespace App\Http\Controllers;

use App\Http\Resources\MainItemShortageResource;
use App\Models\MainItemShortage;
use App\Repositories\MainItemShortageRepository;
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

        return MainItemShortageResource::make($mainItemShortage->load('relatedItems'));
    }

    /**
     * @throws Throwable
     */
    public function destroy($id): JsonResponse
    {
        return DB::transaction(function () use ($id): JsonResponse {
            $mainItemShortage = MainItemShortage::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($mainItemShortage->registerShortages()->exists()) {
                return $this->responseError('این مورد در کسری استفاده شده و امکان حذف آن وجود ندارد.');
            }

            if ($mainItemShortage->delete()) {
                return response()->json(['message' => 'ایتم با موفقیت حذف شد']);
            }

            return $this->responseError('امکان حذف آیتم مورد نظر وجود ندارد.');
        });
    }
}
