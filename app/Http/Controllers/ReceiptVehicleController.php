<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncomingVehicleResource;
use App\Models\Location;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ReceiptVehicleController extends Controller
{


    /**
     * @param Vehicle $vehicle
     * @return JsonResponse|bool
     * @throws AuthorizationException
     */
    public function set(Vehicle $vehicle): JsonResponse|bool
    {
        $this->authorize('set-receipt-vehicle');
        $locationDestinationId = $this->getVehicle($vehicle->getAttribute('vin'));

        if (!$locationDestinationId) {
            return new JsonResponse(
                data: ['message' => 'No suitable location was found for the entered VIN'],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $vehicle->tracking()->create([
            'model_type' => Location::class,
            'model_id' => $locationDestinationId,
            'type' => 'enter',
            'user_id' => Auth::id()
        ]);

        return $vehicle->updateCurrentLocation($locationDestinationId);
    }


    private function getVehicle(string $vin)
    {
        $locationIds = Auth::locations();

        $vehicle = Vehicle::query()
            ->where('vin', $vin)
            ->whereNotIn('current_location', $locationIds)
            ->with([
                'makeCode',
                'carModel',
                'activeBillOfLading',
                'activeBillOfLading.locationSource',
                'activeBillOfLading.locationDestination',
                'activeBillOfLading.car',
            ])
            ->whereHas('activeBillOfLading', function ($q) use ($locationIds) {
                $q->whereIn('location_destination_id', $locationIds);
            })
            ->first();

        return $vehicle?->getAttribute('activeBillOfLading')->location_destination_id ?? null;
    }

    /**
     * @throws AuthorizationException
     */
    public function get(Vehicle $vehicle): IncomingVehicleResource
    {
        $this->authorize('get-receipt-vehicle-information');
        return new IncomingVehicleResource($this->getVehicle($vehicle->getAttribute('vin')));
    }
}
