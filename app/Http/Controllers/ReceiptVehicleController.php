<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncomingVehicleResource;
use App\Models\BillOfLading;
use App\Models\Location;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class ReceiptVehicleController extends Controller
{

    public function set(Request $request, Vehicle $vehicle)
    {
        $this->authorize('set-receipt-vehicle');

        $user = $request->user();
        $locationIds = $user->locations->get()->pluck('id')->all();

        $billOfLading = BillOfLading::query()
            ->with(['items', 'car.driver'])
            ->whereIn('location_destination_id', $locationIds)
            ->whereHasMorph('items', [Vehicle::class], function ($q) use ($locationIds, $vehicle) {
                $q->where('model_id', $vehicle->getAttribute('vin'));
                $q->whereNotIn('current_location', $locationIds);
                $q->where('delivered_to_customer', false);
            })->first();

        $vehicle->tracking()->create([
            'model_type' => Location::class,
            'model_id'   => $billOfLading->destination_source_id,
            'type'       => 'enter',
            'user_id'    => $user->id
        ]);
        return $vehicle->updateCurrentLocation($billOfLading->destination_source_id);
    }

    public function get(Request $request, Vehicle $vehicle)
    {
        $this->authorize('get-receipt-vehicle-information');

        $user = $request->user();
        $locationIds = $user->locations->get()->pluck('id')->all();

        return new IncomingVehicleResource(
            BillOfLading::query()
                ->with(['items', 'car.driver'])
                ->whereIn('location_destination_id', $locationIds)
                ->whereHasMorph('items', [Vehicle::class], function ($q) use ($locationIds, $vehicle) {
                    $q->where('model_id', $vehicle->getAttribute('vin'));
                    $q->whereNotIn('current_location', $locationIds);
                    $q->where('delivered_to_customer', false);
                })->first());
    }
}
