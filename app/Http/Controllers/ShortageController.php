<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShortageItemResource;
use App\Models\ProductionLine;
use App\Models\ShortageItem;
use App\Models\Vehicle;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ShortageController extends Controller
{


    /**
     * @throws ContainerExceptionInterface
     * @throws AuthorizationException
     * @throws NotFoundExceptionInterface
     */
    public function index(Vehicle $vehicle): AnonymousResourceCollection
    {
        $this->authorize('shortages.index');
        $perPage = request()->get('per_page', 15);
        return ShortageItemResource::collection($vehicle->shortageItems()->paginate($perPage));
    }

    /**
     * @throws AuthorizationException
     */
    public function store(Vehicle $vehicle, Request $request): ShortageItemResource|JsonResponse
    {
        $this->authorize('shortages.store');
        $validatedData = $request->validate([
            'erp_product_item_id' => [
                'required',
                'exists:erp_product_items,id',
                Rule::unique('shortage_items', 'erp_product_item_id')
                    ->where('vin', $vehicle->getAttribute('vin'))
                    ->where('status', 'pending')
            ],
        ]);

        if ($vehicle->tracking()->where('model_type', ProductionLine::class)
            ->where('type','exit')->whereIn('model_id',[2,3,4,5])->exists()) {
            return $this->responseError('cant register shortage for vehicle');
        }
        return new ShortageItemResource(ShortageItem::query()->create([
            'erp_product_item_id' => $validatedData['erp_product_item_id'],
            'user_id' => Auth::id(),
            'vin' => $vehicle->getAttribute('vin')
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, Vehicle $vehicle, ShortageItem $shortage): ShortageItemResource|JsonResponse
    {
        $this->authorize('shortages.update');
        $validatedData = $request->validate([
            'status' => ['required', 'in:done',
                Rule::unique('shortage_items')
                    ->where('vin', $vehicle->getAttribute('vin'))
                    ->where('id', $shortage->getAttribute('id'))
            ],
        ]);
        try {
            DB::beginTransaction();
            $params = [
                'vINL' => $vehicle->getAttribute('vin'),
                'wCGroup' => '',
                'qTYCalcP' => 0,
                'withTracking' => 'false',
                'withOutput' => 'false',
                'lackItemNo' => $shortage->getAttribute('erpProductItem')->reference,
                'numberFractionalItems' => 1,
            ];


            $response = ErpWsdlClient::run(config('erp.shortage'), 'PostProduction_Resolving_Shortage', $params);
            if ($response['status'] !== 'ok') {

                throw new Exception($response['message']);
            }
            $shortage->update($validatedData);
            DB::commit();
            return new ShortageItemResource($shortage);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->responseError($exception->getMessage());
        }

    }

}
