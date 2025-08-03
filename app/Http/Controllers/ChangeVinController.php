<?php

namespace App\Http\Controllers;

use App\Filters\ProductionOrderNumberFilter;
use App\Filters\VinFilter;
use App\Http\Requests\StoreChangeVinRequest;
use App\Http\Resources\ChangeVinResource;
use App\Models\ChangeVin;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;


class ChangeVinController extends Controller
{

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
            $data = $request->validated();
            $response = $this->sendErp($data);
            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 400);
            }
            $data = ChangeVin::query()->create($request->validated());
            return ChangeVinResource::make($data);
        } catch (Exception $exception) {
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
