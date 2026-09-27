<?php

namespace App\Http\Controllers\Shortage\V2;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use App\Services\Erp\ErpGetShortageBOM;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ItemsController extends Controller
{
    public function certifiedItems(Request $request, CarModel $carModel): JsonResponse
    {
        try {
            $items = resolve(ErpGetShortageBOM::class)
                ->get($carModel->getAttribute('reference'));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }

        $items = collect($items)
            ->when($request->filled('q'), fn($items) => $items->filter(
                fn($item) => Str::contains($item['line_itm_no'], $request->query('q'), true)
                    || Str::contains($item['line_itm_description'], $request->query('q'), true)
            ))
            ->unique('line_itm_no')
            ->values()
            ->all();

        return response()->json(['data' => $items]);
    }

}
