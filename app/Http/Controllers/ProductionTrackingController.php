<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingRequest;
use App\Http\Resources\TrackingResource;
use App\Http\Resources\VehicleResource;
use App\Models\SendErp;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Services\TrackingService\TrackingServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;

class ProductionTrackingController extends Controller
{

    /**
     * @param  TrackingRequest  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|VehicleResource
     * @throws AuthorizationException
     */
    public function submit(TrackingRequest $request, Vehicle $vehicle): JsonResponse|VehicleResource
    {
        $validatedData = $request->validated();
        if ($validatedData['type'] === 'enter' && $validatedData['model_type']) {
            $this->authorize('production-tracking.'.$validatedData['model_type'].'.enter');
        }
        if ($validatedData['type'] === 'exit' && $validatedData['model_type']) {
            $this->authorize('production-tracking.'.$validatedData['model_type'].'.exit');
        }

        $modelTrackingService = TrackingServiceFactory::getInstance($validatedData['model_type']);
        $data = $modelTrackingService->{$validatedData['type']}($validatedData);
        if (!$data) {
            return $this->responseError('cant not exit or enter to this location', 400);
        }
        $record = $vehicle->tracking()->create($data);

        if (method_exists($modelTrackingService, 'sendToErp')) {
            if (
                ($record->getAttribute('type') === 'enter' && $record->getAttribute('model')->sendErp?->on_enter)
                || ($record->getAttribute('type') === 'exit' && $record->getAttribute('model')->sendErp?->on_exit)
            ) {
                dd('here');
                $modelTrackingService->sendToErp($record);
            }
        }


        return new VehicleResource($vehicle);
    }

    /**
     * @param  Tracking  $tracking
     * @return TrackingResource
     */
    public function update(Tracking $tracking): TrackingResource
    {
        $tracking->update(['data' => null]);

        $modelTrackingService = TrackingServiceFactory::getInstance($tracking->getAttribute('model')->getTable());
        $type = $tracking->getAttribute('type') === 'enter' ? 'on_enter' : 'on_exit';

        if (method_exists($modelTrackingService, 'sendToErp') &&
            SendErp::query()->where('model_type', $tracking->getAttribute('model_type'))
                ->where($type, true)
                ->where('model_id', $tracking->getAttribute('model_id'))
                ->exists()) {
            $modelTrackingService->sendToErp($tracking);
        }


        return new TrackingResource($tracking);
    }
}
