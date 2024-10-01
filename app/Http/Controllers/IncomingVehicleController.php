<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncomingVehicleResource;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncomingVehicleController extends Controller
{

    /**
     * @throws AuthorizationException
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $this->authorize('incoming-vehicles');

        $user = $request->user();
        $locationIds = $user->locations()->get()->pluck('id')->all();

        return IncomingVehicleResource::collection(
            Vehicle::query()
                ->whereNotIn('current_location', $locationIds)
                ->with([
                    'carModel',
                    'makeCode',
                    'activeBillOfLading',
                    'activeBillOfLading.locationSource',
                    'activeBillOfLading.locationDestination',
                    'activeBillOfLading.car',
                ])
                ->whereHas('activeBillOfLading', function ($q) use ($locationIds) {
                    $q->whereIn('location_destination_id', $locationIds);
                })
                ->paginate()
        );
    }
}
