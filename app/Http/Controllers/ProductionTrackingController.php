<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\TrackingService\TrackingServiceFactory;
use Illuminate\Http\JsonResponse;

class ProductionTrackingController extends Controller
{
    /**
     * @param  TrackingRequest  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|VehicleResource
     */
    public function submit(TrackingRequest $request, Vehicle $vehicle): JsonResponse|VehicleResource
    {
        $validatedData = $request->validated();
        $modelTrackingService = TrackingServiceFactory::getInstance($validatedData['model_type']);
        $data = $modelTrackingService->{$validatedData['type']}($validatedData);
        if (!$data) {
            return response()->json([
                'message' => 'cant not exit or enter to this location'
            ]);
        }
        $vehicle->tracking()->create($data);
        return new VehicleResource($vehicle);
    }
}
