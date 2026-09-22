<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegisterShortageRequest;
use App\Http\Requests\UpdateRegisterShortageRequest;
use App\Http\Resources\RegisterShortageResource;
use App\Models\RegisterShortage;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class RegisterShortageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RegisterShortage::class, 'register_shortage');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $registerShortages = RegisterShortage::query()
            ->with(['vehicle', 'carModel', 'makeCode', 'user'])
            ->latest()
            ->paginate($perPage);

        return RegisterShortageResource::collection($registerShortages);
    }

    public function store(StoreRegisterShortageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $vehicle = Vehicle::query()->findOrFail($validated['vin']);

        $registerShortage = RegisterShortage::query()->create([
            ...$validated,
            'car_model_id' => $vehicle->getAttribute('car_model_id'),
            'make_code_id' => $vehicle->getAttribute('make_code_id'),
            'status' => RegisterShortage::STATUS_HAS_SHORTAGE,
            'user_id' => Auth::id(),
        ]);

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
        RegisterShortage $registerShortage
    ): RegisterShortageResource {
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
