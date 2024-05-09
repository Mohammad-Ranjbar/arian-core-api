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

        return IncomingVehicleResource::collection(
            Vehicle::query()
                ->with('activeBillOfLading')
                ->whereHas('activeBillOfLading',function ($q) use($locationIds){
                    $q->whereIn('location_destination_id', $locationIds);
                })
            ->paginate()
        );
    }
}
