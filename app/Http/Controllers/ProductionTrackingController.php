<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\TrackingService\TrackingServiceFactory;

class ProductionTrackingController extends Controller
{
    public function submit(TrackingRequest $request, Vehicle $vehicle)
    {
        $validatedData = $request->validated();
        $validatedData['vin'] = $vehicle->vin;
        $validatedData['user_id'] = $request->user()->id;

        $modelTrackingService = TrackingServiceFactory::getInstance($validatedData['model_type']);
        if (!$data = $modelTrackingService->{$validatedData['type']}($validatedData)) {
            return response()->json([
                'message' => 'cant not exit or enter to this location'
            ]);
        }
        $vehicle->tracking()->create($data);

        return new VehicleResource($vehicle);
    }
}
