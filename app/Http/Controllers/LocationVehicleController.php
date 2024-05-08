<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationVehicleController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {

        return VehicleResource::collection(
            Vehicle::query()
                ->whereIn('current_location', Auth::user()->locations()->get()->pluck('id')->all())
                ->paginate());

    }
}
