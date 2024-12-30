<?php

namespace App\Http\Controllers;

use App\Models\ErpProductItem;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetVehicleComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ComponentItemController extends Controller
{
    public function __construct(private readonly ErpGetVehicleComponent $component)
    {
    }

    public function all(Vehicle $vehicle): JsonResponse
    {
        return $this->main($vehicle, false);
    }

    /**
     * @throws ValidationException
     */
    public function main(Vehicle $vehicle, $filter = true): JsonResponse
    {
        $items = collect($this->component->get($vehicle->getAttribute('production_order_number')));
        $items->each(function ($item) {
            $erpProductItem = ErpProductItem::query()->where('reference', $item->Item_No)->first();
            if (empty($erpProductItem)) {
                $erpProductItem = $this->component->getItem($item->Item_No);
            }
            $item->productItem = $erpProductItem;
        });
        if ($filter) {
            $items = $items
                ->filter(function ($item) {
                    return $item->Main_Component;
                });
        }

       $items = $items->map(function ($item) use ($items) {
            if (!$item->productItem) {
                return [
                    'id' => 0,
                    'title' => 'unknown item for ' . $item->Item_No,
                    'reference' => 'unknown item for ' . $item->Item_No,
                    'description' => 'unknown item for ' . $item->Item_No,
                    'related' => []
                ];
            }
            return [
                'id' => $item->productItem->id,
                'title' => $item->productItem->title,
                'reference' => $item->productItem->reference,
                'description' => $item->productItem->description,
                'routing_link_code' => $item->Routing_Link_Code	,
                'related' => $items->filter(function ($row) use ($item) {
                    return $row->Related_Component == $item->Item_No && $row->Item_No != $item->Item_No;
                })->pluck('productItem.title')
            ];
        });

        return response()->json($items->values());
    }
}
