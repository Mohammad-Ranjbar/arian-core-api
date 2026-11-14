<?php

namespace App\Http\Controllers;

use App\Filters\VehicleAllocationFilter;
use App\Http\Requests\IndexVehicleAllocationRequest;
use App\Http\Resources\VehicleAllocationResource;
use App\Models\VehicleAllocation;
use App\Services\Erp\RestApi\ProductionPostingBINsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Pipeline;
use Throwable;

class VehicleAllocationController extends Controller
{
    public function index(IndexVehicleAllocationRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $allocations = Pipeline::send(VehicleAllocation::query()
            ->whereIn('make_code_id', $request->user()->makeCodes()->select('make_codes.id')))
            ->through([VehicleAllocationFilter::class])
            ->thenReturn()
            ->with([
                'makeCode:id,title,reference',
                'carModel' => fn($query) => $query->withoutEagerLoads()->select('id', 'title', 'reference'),
                'user:id,name',
            ])
            ->latest('id')
            ->paginate($validated['per_page'] ?? 25)
            ->withQueryString();

        return VehicleAllocationResource::collection($allocations);
    }

    /**
     * @throws Throwable
     */
    public function send(VehicleAllocation $vehicleAllocation): JsonResponse
    {
        try {
            $vehicleAllocation->update(['user_id' => Auth::id()]);
            $result = resolve(ProductionPostingBINsService::class)->send(
                $vehicleAllocation->getAttribute('vin'), wcGroup: '', withTracking: 'false', withOutput: 'false', itemType: 2, model: $vehicleAllocation
            )->unique();

            return response()->json($result, $result['http_status']);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }

    }
}
