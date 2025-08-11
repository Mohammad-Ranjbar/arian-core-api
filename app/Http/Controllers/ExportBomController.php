<?php

namespace App\Http\Controllers;

use App\Exports\BomExport;
use App\Models\ErpProductItem;
use App\Services\Erp\ErpGetVehicleComponent;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportBomController extends Controller
{

    public function __construct(private readonly ErpGetVehicleComponent $component)
    {

    }

    public function __invoke(
        Request $request,
        $po
    ): BinaryFileResponse|JsonResponse {
        try {
            $components = collect($this->component->get($po))->filter(function (
                $item
            ) {
                return $item->Quantity > 0;
            });
            $components->each(function ($item) {
                $erpProductItem = ErpProductItem::query()->where('reference', $item->Item_No)->first();
                if (empty($erpProductItem)) {
                    $erpProductItem = $this->component->getItem($item->Item_No);
                }
                $item->productItem = $erpProductItem;
            });

            $list = $components->map(function ($item) {
                return [
                    $item->productItem?->title,
                    $item->productItem?->description,
                    $item->productItem?->reference,
                    $item->Quantity
                ];
            });

            return Excel::download(new BomExport($list), uniqid().'.xlsx');

        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }
}
