<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignItemToAlternativeCategoryRequest;
use App\Http\Resources\AlternativeCategoryResource;
use App\Models\AlternativeCategory;
use App\Repositories\AlternativeCategoryRepository;
use Exception;

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
            if ($alternativeCategory->items()->where('erp_product_item_id', $validatedData['erp_product_item_id'])->exists()) {
                return $this->responseError('ایتم مورد نظر در لیست ایتم های این گروه موجود است');
            }
            $alternativeCategory->items()->create($validatedData);
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
