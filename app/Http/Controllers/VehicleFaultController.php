<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIncidentRequest;
use App\Http\Requests\VehicleFaultRequest;
use App\Http\Resources\VehicleIncidentResource;
use App\Models\Vehicle;
use App\Repositories\IncidentSourceRepository;
use App\Repositories\VehicleIncidentRepository;

class VehicleFaultController extends Controller
{

    private VehicleIncidentRepository $repository;
    private IncidentSourceRepository  $incidentSourceRepository;

    /**
     * @param  VehicleIncidentRepository  $repository
     * @param  IncidentSourceRepository  $incidentSourceRepository
     */
    public function __construct(
        VehicleIncidentRepository $repository,
        IncidentSourceRepository $incidentSourceRepository
    ) {
        $this->repository = $repository;
        $this->incidentSourceRepository = $incidentSourceRepository;
    }

    public function submit(VehicleFaultRequest $request, Vehicle $vehicle, $type)
    {
        $validatedData = $request->validated();
        $tracking = $vehicle->tracking()->orderByDesc('id')->first();
        if (!$tracking) {
            return response()->json([
                'message' => 'can not find vehicle location'
            ], 422);
        }
        $validatedData['production_tracking_id'] = $tracking->id;
        $this->incidentSourceRepository->upsert([
            'incident_id'  => $validatedData['incident_id'],
            'element_id'   => $validatedData['element_id'],
            'car_model_id' => $vehicle->carModel->id,
        ]);
        return new VehicleIncidentResource($this->repository->store($validatedData));
    }

    public function index(Vehicle $vehicle, $type)
    {
        $this->authorize("vehicles.faults.{$type}.index");

        request()->merge([
            'type' => request()->route()->parameter('type'),
            'vin'  => request()->route()->parameter('vehicle')->vin,
        ]);

        $perPage = request()->input('per_page', 15);
        return VehicleIncidentResource::collection($this->repository->paginate($perPage));
    }

}
