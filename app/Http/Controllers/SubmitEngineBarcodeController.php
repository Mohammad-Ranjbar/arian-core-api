<?php

namespace App\Http\Controllers;

use App\Http\Requests\EngineBarcodeRequest;
use App\Http\Resources\BarcodeResource;
use App\Models\Barcode;
use App\Models\ErpProductItem;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetVehicleComponent;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SubmitEngineBarcodeController extends Controller
{
    /**
     * @param ErpGetVehicleComponent $erpGetVehicleComponent
     */
    public function __construct(public ErpGetVehicleComponent $erpGetVehicleComponent)
    {

    }


    /**
     * @param Vehicle $vehicle
     * @param EngineBarcodeRequest $request
     * @return BarcodeResource|JsonResponse
     */
    public function __invoke(Vehicle $vehicle, EngineBarcodeRequest $request): BarcodeResource|JsonResponse
    {
        $validatedData = $request->validated();

        $submitted = Cache::get('barcode_submitted_' . $validatedData['code']);
        if ($submitted) {
            return new JsonResponse(
                data: ['message' => 'barcode submitted'],
                status: 400
            );
        }
        Cache::set('barcode_submitted_' . $validatedData['code'], true, 10);

        DB::beginTransaction();
        try {

            Barcode::query()->where('vin', $vehicle->getAttribute('vin'))
                ->whereHas('erpProductItem', function ($q) {
                    $q->where('type', ErpProductItem::ENGINE);
                })->delete();

            $items = collect($this->erpGetVehicleComponent->get($vehicle->getAttribute('production_order_number')))
                ->pluck('Item_No')->all();
            $erpItem = ErpProductItem::query()
                ->where('type', ErpProductItem::ENGINE)
                ->whereIn('reference', $items)
                ->firstOrFail();

            $new = Barcode::query()->create([
                'vin' => $vehicle->getAttribute('vin'),
                'user_id' => Auth::id(),
                'code' => $validatedData['code'],
                'erp_product_item_id' => $erpItem->getAttribute('id')
            ]);

            DB::commit();
            return new BarcodeResource($new);

        } catch (Exception $exception) {
            DB::rollBack();
            return new JsonResponse(
                data: ['message' => $exception->getMessage()],
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }


    }
}
