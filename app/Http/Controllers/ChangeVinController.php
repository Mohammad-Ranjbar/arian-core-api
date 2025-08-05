<?php

namespace App\Http\Controllers;

use App\Filters\ProductionOrderNumberFilter;
use App\Filters\VinFilter;
use App\Http\Requests\StoreChangeVinRequest;
use App\Http\Resources\ChangeVinResource;
use App\Models\ChangeVin;
use App\Models\ProductionLine;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Models\VinEngine;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;


class ChangeVinController extends Controller
{

    public function __construct(protected VehicleController $vehicleController)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('change-vins.index');
        $data = Pipeline::send(ChangeVin::query())->through([
            VinFilter::class,
            ProductionOrderNumberFilter::class
        ])->thenReturn()->paginate(request()->input('per_page', 20));

        return ChangeVinResource::collection($data);
    }

    public function store(StoreChangeVinRequest $request): JsonResponse|ChangeVinResource
    {
        try {

            DB::beginTransaction();

            $data = $request->validated();
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->where('production_order_number', $data['production_order_number'])->first();
            $tracking = $vehicle->tracking()->where('type', 'enter')->where('model_type', ProductionLine::class)->first();
            if (empty($tracking)) {
                return $this->responseError('شماره وارد شده ورود به خط ندارد');
            }

            $response = $this->sendErp($data);
            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 400);
            }

            $this->vehicleController->getData($data['production_order_number']);

            Tracking::query()->create([
                'type' => 'enter',
                'vin' => $data['vin'],
                'model_type' => ProductionLine::class,
                'model_id' => $tracking->model_id,
                'user_id' => Auth::id(),
            ]);
            $changeVin = ChangeVin::query()->create($data);
            VinEngine::query()
                ->where('vin', $data['vin'])
                ->update([
                    'production_order_number' => $data['production_order_number'],
                ]);
            DB::commit();
            return ChangeVinResource::make($changeVin);

        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function sendErp($data): array
    {
        return ErpWsdlClient::run(config('erp.vehicle_change'), 'VINRegistrationChanges', [
            'productionNoP' => $data['production_order_number'],
            'tempVINP' => $data['old_vin'],
            'vINP' => $data['vin'],
        ]);
    }
}
