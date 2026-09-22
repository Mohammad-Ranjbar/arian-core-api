<?php

namespace App\Http\Controllers;

use App\Http\Resources\RelatedItemShortageResource;
use App\Models\MainItemShortage;
use App\Repositories\RelatedItemShortageRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RelatedItemShortageController extends CrudController
{

    public function __construct(public readonly RelatedItemShortageRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(RelatedItemShortageResource::class);
    }

    /**
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        if (!isset($this->repository)) {
            return $this->responseError('repository not found', 400);
        }
        $main = MainItemShortage::query()->where('id','main_item_shortage_id')->first();
        if ($validatedData['item_no'] == $main->getAttribute('item_no')){
            return $this->responseError('آیتم وابسته نمیتواند هم نوع آیتم اصلی باشد', 400);
        }
        if ($main->relatedItems()->where('item_no',$validatedData['item_no'])->exists()) {
            return $this->responseError('ایتم انتخابی در ایتم های وابسته این مورد موجود است');
        }
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(), $this->customStoreRules()));
        $validatedData = array_merge($validatedData, $this->appendStoreData);
        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }
        try {
            DB::beginTransaction();
            $saveItem = $this->repository->store($validatedData);
            DB::commit();
            if ($saveItem->relatedItems->count() == 0) {
                return response()->json([
                    'message' => 'تغییرات با موفقیت انجام شد'
                ]);
            }
            return new $this->resource($saveItem->relatedItems()->latest()->first());
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
