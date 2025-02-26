<?php

namespace App\Http\Controllers;

use App\Http\Requests\SynchronousTypeItemRequest;
use App\Http\Resources\SynchronousTypeItemResource;
use App\Models\ErpProductItem;
use App\Models\SynchronousType;
use App\Models\SynchronousTypeItem;
use App\Services\Erp\ErpCrossReference;
use Illuminate\Http\JsonResponse;

class SynchronousTypeItemController extends Controller
{

    public function __construct(private readonly ErpCrossReference $erpCrossReference)
    {
    }

    public function store(SynchronousTypeItemRequest $request, SynchronousType $synchronousType): JsonResponse|SynchronousTypeItemResource
    {
        $validatedData = $request->validated();
        if ($synchronousType->items()
            ->where('is_active', true)
            ->where('car_model_id', $validatedData['car_model_id'])
            ->exists()) {
            return response()->json([
                'message' => 'برای این مدل خودرو قبلا ثبت شده است'
            ],422);
        }
        $erpProductItem = ErpProductItem::query()->findOrFail($validatedData['erp_product_item_id']);
        $crossReferences = $this->erpCrossReference->get($erpProductItem->getAttribute('reference'));

        if (!empty($validatedData['cross_reference']) && !in_array($validatedData['cross_reference'], $crossReferences)) {
            return response()->json([
                'message' => 'کراس رفرنس وارد شده نامعتبر است'
            ],422);
        }
        $validatedData['synchronous_type_id'] = $synchronousType->getAttribute('id');
        $validatedData['is_active'] = true;
        return new SynchronousTypeItemResource(SynchronousTypeItem::query()->create($validatedData));
    }

    public function destroy(SynchronousTypeItem $synchronousTypeItem): JsonResponse
    {
        $synchronousTypeItem->update([
            'is_active' => false
        ]);

        return new JsonResponse(
            data: ['message' => 'با موفقیت حذف گردید']
        );
    }
}
