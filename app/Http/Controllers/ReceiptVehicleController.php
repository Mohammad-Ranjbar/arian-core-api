<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncomingVehicleResource;
use App\Models\BillOfLading;
use App\Models\Location;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ReceiptVehicleController extends Controller
{

    public function set(Request $request, Vehicle $vehicle)
    {
        $this->authorize('set-receipt-vehicle');


        $data = $this->getVehicle($vehicle->getAttribute('vin'));

        $vehicle->tracking()->create([
            'model_type' => Location::class,
            'model_id'   => $data->activeBillOfLading->location_destination_id,
            'type'       => 'enter',
            'user_id'    => auth()->id()
        ]);

        return $vehicle->updateCurrentLocation($data->activeBillOfLading->location_destination_id);
    }

    /**
     * @throws AuthorizationException
     */
    public function get(Vehicle $vehicle): IncomingVehicleResource
    {
        $this->authorize('get-receipt-vehicle-information');
        return new IncomingVehicleResource($this->getVehicle($vehicle->getAttribute('vin')));
    }

    private function getVehicle(
        $vin
    ): Model|Builder {
        $locationIds = auth()->user()->locations()->get()->pluck('id')->all();
        return Vehicle::query()
            ->where('vin', $vin)
            ->whereNotIn('current_location', $locationIds)
            ->with([
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
}
