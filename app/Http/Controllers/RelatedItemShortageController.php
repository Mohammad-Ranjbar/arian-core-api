<?php

namespace App\Http\Controllers;

use App\Http\Resources\RelatedItemShortageResource;
use App\Models\MainItemShortage;
use App\Models\RelatedItemShortage;
use App\Repositories\RelatedItemShortageRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class RelatedItemShortageController extends CrudController
{
    public function __construct(public readonly RelatedItemShortageRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(RelatedItemShortageResource::class);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(), $this->customStoreRules()));
        $main = MainItemShortage::query()->findOrFail($validatedData['main_item_shortage_id']);
        if (($validatedData['item_no'] ?? null) == $main->getAttribute('item_no')) {
            return $this->responseError('آیتم وابسته نمیتواند هم نوع آیتم اصلی باشد', 400);
        }
        if (RelatedItemShortage::query()->where('main_item_shortage_id', $main->getAttribute('id'))
            ->where('item_no', $validatedData['item_no'] ?? null)->exists()) {
            return $this->responseError('ایتم انتخابی در ایتم های وابسته این مورد موجود است');
        }
        $validatedData = array_merge($validatedData, $this->appendStoreData);
        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }
        try {
            DB::beginTransaction();
            $saveItem = $this->repository->store($validatedData);
            DB::commit();
            $message = $saveItem->getKey() !== $main->getKey()
                ? "نسخه جدید قطعه اصلی «{$saveItem->description}» با شماره نسخه {$saveItem->version} ساخته شد."
                : null;

            if ($saveItem->relatedItems->count() == 0) {
                return response()->json([
                    'message' => $message ?? 'تغییرات با موفقیت انجام شد',
                ]);
            }

            $resource = new $this->resource($saveItem->relatedItems()->latest('id')->first());

            return $message ? $resource->additional(['message' => $message]) : $resource;
        } catch (Exception $exception) {
            DB::rollBack();

            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
