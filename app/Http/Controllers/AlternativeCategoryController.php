<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignItemToAlternativeCategoryRequest;
use App\Http\Resources\AlternativeCategoryResource;
use App\Models\AlternativeCategory;
use App\Models\AlternativeItem;
use App\Models\ErpProductItem;
use App\Repositories\AlternativeCategoryRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class AlternativeCategoryController extends CrudController
{
    public function __construct(public readonly AlternativeCategoryRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AlternativeCategoryResource::class);
    }

    public function assignItem(AlternativeCategory $alternativeCategory, AssignItemToAlternativeCategoryRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $error = DB::transaction(function () use ($alternativeCategory, $validatedData) {
                // Serialize assignments of the same ERP product, including across categories.
                ErpProductItem::query()->whereKey($validatedData['erp_product_item_id'])->lockForUpdate()->firstOrFail();

                $existing = AlternativeItem::query()
                    ->with(['user' => fn ($query) => $query->withTrashed()])
                    ->where('erp_product_item_id', $validatedData['erp_product_item_id'])
                    ->where('user_id', '!=', $validatedData['user_id'])
                    ->first();

                if ($existing) {
                    $name = $existing->user?->name ?? 'نامشخص';
                    return "این قطعه با کد انتخابی برای کاربر دیگری با نام {$name} ایجاد شده است.";
                }

                if ($alternativeCategory->items()->where('erp_product_item_id', $validatedData['erp_product_item_id'])->exists()) {
                    return 'ایتم مورد نظر در لیست ایتم های این گروه موجود است';
                }

                $alternativeCategory->items()->create($validatedData);
                return null;
            });

            if ($error !== null) {
                return $this->responseError($error);
            }
            $alternativeCategory->refresh();
            return AlternativeCategoryResource::make($alternativeCategory);

        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    public function deleteItem(AlternativeCategory $alternativeCategory, $erpItemId)
    {
        try {
            logger($erpItemId);
            logger($alternativeCategory->items);
            if ($alternativeCategory->items()->where('id', $erpItemId)->doesntExist()) {
                return $this->responseError('ایتم مورد نظر در لیست ایتم های این گروه الترناتیو نیست');
            }
            $alternativeCategory->items()->where('id', $erpItemId)->delete();
            $alternativeCategory->refresh();
            return AlternativeCategoryResource::make($alternativeCategory);

        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }
}
