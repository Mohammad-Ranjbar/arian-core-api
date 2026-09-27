<?php

namespace App\Http\Controllers;

use App\Filters\VinExactFilter;
use App\Filters\VinFilter;
use App\Http\Requests\StoreRegisterShortageRequest;
use App\Http\Requests\UpdateRegisterShortageRequest;
use App\Http\Requests\VinReferenceRequest;
use App\Http\Resources\RegisterShortageResource;
use App\Models\MainItemShortage;
use App\Models\RegisterShortage;
use App\Models\Vehicle;
use App\Services\Erp\ErpGetWorkCenterFromRoutingLink;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Pipeline;

class RegisterShortageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RegisterShortage::class, 'register_shortage');
    }

    public function shortagesVehicle(string $vin)
    {
        try {
            $vehicle = Vehicle::query()->where('vin', $vin)->with('shortageItems')->firstOrFail();
            $shortages = RegisterShortage::query()->where('vin', $vin)->latest()->get();
            return RegisterShortageResource::collection($shortages);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    public function setShortagesVehicle(VinReferenceRequest $request, string $vin)
    {
        try {
            $vehicle = Vehicle::query()
                ->where('vin', $vin)
                ->with('shortageItems')
                ->firstOrFail();

            $destinationVin = $request->input('vin');
            if ($destinationVin == $vin) {
                return $this->responseError("Vin is different from source vin");
            }
            $sourceShortages = RegisterShortage::query()
                ->where('vin', $vin)
                ->get();

            foreach ($sourceShortages as $shortage) {
                $newShortage = $shortage->replicate();
                $newShortage->vin = $destinationVin;
                $newShortage->user_id = Auth::id();
                $newShortage->save();
            }

            $shortages = RegisterShortage::query()
                ->where('vin', $destinationVin)
                ->latest()
                ->get();

            return RegisterShortageResource::collection($shortages);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $data = Pipeline::send(RegisterShortage::query()
            ->with(['vehicle', 'carModel', 'makeCode', 'user'])
            ->latest())
            ->through([
                VinExactFilter::class,
            ])
            ->thenReturn()
            ->paginate($perPage);

        return RegisterShortageResource::collection($data);
    }

    public function store(StoreRegisterShortageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $vehicle = Vehicle::query()->findOrFail($validated['vin']);
        $carModel = $vehicle->carModel;
        $workCenter = resolve(ErpGetWorkCenterFromRoutingLink::class)
            ->get($carModel->reference);
        dd($workCenter);
        $registerShortage = RegisterShortage::query()->create([
            ...$validated,
            'car_model_id' => $vehicle->getAttribute('car_model_id'),
            'make_code_id' => $vehicle->getAttribute('make_code_id'),
            'status' => RegisterShortage::STATUS_HAS_SHORTAGE,
            'user_id' => Auth::id(),
        ]);
        if ($registerShortage->getAttribute('type') === 'main'){
           $mainItem = MainItemShortage::query()->where('item_no',$validated['item_no'])->with('relatedItems')->first();
            foreach ($mainItem->relatedItems as $relatedItem){
                $validated['item_no'] = $relatedItem->getAttribute('item_no');
                $registerShortage = RegisterShortage::query()->create([
                    ...$validated,
                    'car_model_id' => $vehicle->getAttribute('car_model_id'),
                    'make_code_id' => $vehicle->getAttribute('make_code_id'),
                    'status' => RegisterShortage::STATUS_HAS_SHORTAGE,
                    'user_id' => Auth::id(),
                ]);
            }
        }

        return RegisterShortageResource::make(
            $registerShortage->load(['vehicle', 'carModel', 'makeCode', 'user'])
        )->response()->setStatusCode(201);
    }

    public function show(RegisterShortage $registerShortage): RegisterShortageResource
    {
        return RegisterShortageResource::make(
            $registerShortage->load(['vehicle', 'carModel', 'makeCode', 'user'])
        );
    }

    public function update(
        UpdateRegisterShortageRequest $request,
        RegisterShortage              $registerShortage
    ): RegisterShortageResource
    {
        $validated = $request->validated();

        if (array_key_exists('vin', $validated)) {
            $vehicle = Vehicle::query()->findOrFail($validated['vin']);
            $validated['car_model_id'] = $vehicle->getAttribute('car_model_id');
            $validated['make_code_id'] = $vehicle->getAttribute('make_code_id');
        }

        if (($validated['type'] ?? null) === RegisterShortage::TYPE_SINGLE) {
            $validated['work_center'] = null;
        }

        $registerShortage->update($validated);

        return RegisterShortageResource::make(
            $registerShortage->refresh()->load(['vehicle', 'carModel', 'makeCode', 'user'])
        );
    }

    public function destroy(RegisterShortage $registerShortage): JsonResponse
    {
        $registerShortage->delete();

        return response()->json([
            'message' => 'Register shortage deleted successfully.',
        ]);
    }
}
