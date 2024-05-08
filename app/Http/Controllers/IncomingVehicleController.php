<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncomingVehicleResource;
use App\Models\BillOfLading;
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
            ->with('vehicles', function ($query) use ($locationIds) {
                $query->whereNotIn('current_location', $locationIds);
            })
            ->whereIn('location_destination_id', $locationIds)
            ->whereHas('vehicles')
            ->where('status','!=','discharge')
            ->paginate()
        );
    }
}
