<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncomingVehicleResource;
use App\Models\Location;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReceiptVehicleController extends Controller
{


    /**
     * @param Vehicle $vehicle
     * @return bool
     * @throws AuthorizationException
     */
    public function set(Vehicle $vehicle): bool
    {
        $this->authorize('set-receipt-vehicle');

        $data = $this->getVehicle($vehicle->getAttribute('vin'));

        $vehicle->tracking()->create([
            'model_type' => Location::class,
            'model_id' => $data->activeBillOfLading->location_destination_id,
            'type' => 'enter',
            'user_id' => Auth::id()
        ]);

        return $vehicle->updateCurrentLocation($data->activeBillOfLading->location_destination_id);
    }


    /**
     * @param string $vin
     * @return Model|Builder
     */
    private function getVehicle(string $vin): Model|Builder
    {
        $locationIds = Auth::locations();

        return Vehicle::query()
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
            ->firstOrFail();
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
