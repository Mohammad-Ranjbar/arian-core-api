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
            ->with(['vehicles', 'car.driver'])
            ->whereIn('location_destination_id', $locationIds)
            ->whereHas('vehicles', function ($q) use ($locationIds) {
                $q->whereNotIn('current_location', $locationIds);
                $q->whereNull('delivered_customer_at');
            })->paginate()
        );
    }
}
