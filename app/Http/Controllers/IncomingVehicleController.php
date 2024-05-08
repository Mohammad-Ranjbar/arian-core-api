<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncomingVehicleResource;
use App\Models\BillOfLading;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class IncomingVehicleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $this->authorize('incoming-vehicles');

        $user = $request->user();
        $locationIds = $user->locations()->get()->pluck('id')->all();

        return IncomingVehicleResource::collection(BillOfLading::query()
            ->with(['items', 'car.driver'])
            ->whereIn('location_destination_id', $locationIds)
            ->whereHasMorph('items', [Vehicle::class], function ($q) use($locationIds){
                $q->whereNotIn('current_location', $locationIds);
                $q->where('delivered_to_customer', false);
            })->paginate()
        );
    }
}
