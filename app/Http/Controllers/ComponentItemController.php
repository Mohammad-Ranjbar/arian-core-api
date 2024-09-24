<?php

namespace App\Http\Controllers;

use App\Models\ErpProductItem;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetVehicleComponent;

class ComponentItemController extends Controller
{
    public function __construct(private readonly ErpGetVehicleComponent $component)
    {
    }

    public function __invoke(Vehicle $vehicle)
    {
        $items = collect($this->component->get($vehicle->getAttribute('product_order_number')))
//            ->filter(function ($item) {
//                return $item->Main_Component;
//            })
            ->map(function ($item) {
                $erpProductItem = ErpProductItem::query()->where('reference', $item->Item_No)->first();
                if (empty($erpProductItem)) {
                    $erpProductItem = $this->component->getItem($item->Item_No);
                }
                if (!$erpProductItem) {
                    return [
                        'id' => 0,
                        'title' => 'unknown item for ' . $item->Item_No,
                        'reference' => 'unknown item for ' . $item->Item_No,
                        'description' => 'unknown item for ' . $item->Item_No,
                        'is_main' => false,
                    ];
                }
                return [
                    'id' => $erpProductItem->id,
                    'title' => $erpProductItem->title,
                    'reference' => $erpProductItem->reference,
                    'description' => $erpProductItem->description,
                    'is_main' => $item->Main_Component,
                    'related_to' => $item->Related_Component,
                ];
            });

        return response()->json($items);
    }
}
