<?php

namespace App\Http\Controllers;

use App\Http\Resources\InspectionResource;
use App\Models\Inspection;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class InspectionController extends Controller
{
    /**
     * @param  Request  $request
     * @param  Vehicle  $vehicle
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request, Vehicle $vehicle): AnonymousResourceCollection
    {
        $this->authorize('inspections.index');
        $perPage = $request->get('per_page', 15);
        return InspectionResource::collection($vehicle->inspections()->paginate($perPage));
    }

    /**
     * @param  Vehicle  $vehicle
     * @param  Request  $request
     * @return InspectionResource
     * @throws AuthorizationException
     */
    public function store(Vehicle $vehicle, Request $request): InspectionResource
    {
        $this->authorize('inspections.store');
        $validatedData = $request->validate([
            'data' => 'required|json'
        ]);

        $inspection = $vehicle->inspections()->create([
            'user_id' => Auth::id(),
            'data'    => $validatedData['data']
        ]);

        return new InspectionResource($inspection);
    }


    /**
     * @param  Vehicle  $vehicle
     * @param  Inspection  $inspection
     * @return InspectionResource
     * @throws AuthorizationException
     */
    public function show(Vehicle $vehicle, Inspection $inspection): InspectionResource
    {
        $this->authorize('inspections.show');
        return new InspectionResource($inspection);
    }


}
