<?php

namespace App\Http\Controllers;

use App\Models\ErpProductItem;
use App\Services\Erp\ErpCrossReference;
use Illuminate\Http\JsonResponse;

class ErpProductItemController extends Controller
{

    public function __construct(private readonly ErpCrossReference $erpCrossReference)
    {
    }

    public function crossReferences(ErpProductItem $erpProductItem): JsonResponse
    {
        return response()->json($this->erpCrossReference->get($erpProductItem->getAttribute('reference')));
    }

}
