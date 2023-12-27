<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingRequest;
use App\Http\Requests\UpdateTrackingRequest;
use App\Http\Resources\TrackingResource;
use App\Http\Resources\VehicleResource;
use App\Jobs\TrackingJob;
use App\Models\Tracking;
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
            return $this->responseError('cant not exit or enter to this location', 400);
        }
        $record = $vehicle->tracking()->create($data);
        TrackingJob::dispatch($record->toArray())->onQueue('http');
        return new VehicleResource($vehicle);
    }

    /**
     * @param  UpdateTrackingRequest  $request
     * @param  Tracking  $tracking
     * @return TrackingResource
     */
    public function update(UpdateTrackingRequest $request, Tracking $tracking): TrackingResource
    {
        $validatedData = $request->validated();
        $validatedData['data'] = [];
        $tracking->update($validatedData);

        TrackingJob::dispatch($tracking->toArray())->onQueue('http');

        return new TrackingResource($tracking);
    }
}
