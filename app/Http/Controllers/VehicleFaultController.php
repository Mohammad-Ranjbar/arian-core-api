<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentSourceRequest;
use App\Http\Requests\VehicleFaultRequest;
use App\Http\Resources\IncidentSourceResource;
use App\Http\Resources\VehicleIncidentResource;
use App\Models\IncidentSource;
use App\Models\Vehicle;
use App\Repositories\IncidentSourceRepository;
use App\Repositories\VehicleIncidentRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleFaultController extends Controller
{

    /**
     * @var VehicleIncidentRepository
     */
    private VehicleIncidentRepository $repository;
    /**
     * @var IncidentSourceRepository
     */
    private IncidentSourceRepository $incidentSourceRepository;

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

    /**
     * @param  VehicleFaultRequest  $request
     * @param  Vehicle  $vehicle
     * @return VehicleIncidentResource|JsonResponse
     */
    public function submit(VehicleFaultRequest $request, Vehicle $vehicle): VehicleIncidentResource|JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $tracking = $vehicle->tracking()->orderByDesc('id')->first();
            if (!$tracking) {
                return response()->json([
                    'message' => 'can not find vehicle location'
                ], 422);
            }
            $validatedData['production_tracking_id'] = $tracking->id;
            $this->incidentSourceRepository->upsert([
                'incident_id'  => $validatedData['incident_id'], 'element_id' => $validatedData['element_id'],
                'car_model_id' => $vehicle->carModel->id,
            ]);
            return new VehicleIncidentResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }

    }

    /**
     * @param  Vehicle  $vehicle
     * @param $type
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(
        Vehicle $vehicle,
        $type
    ): JsonResponse|AnonymousResourceCollection {
        try {
            $this->authorize("vehicles.faults.$type.index");

            request()->merge([
                'type' => request()->route()->parameter('type'), 'vin' => $vehicle->vin,
            ]);

            $perPage = request()->input('per_page', 15);
            return VehicleIncidentResource::collection($this->repository->paginate($perPage));
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }

    }

    /**
     * @param  IncidentSourceRequest  $request
     * @param  IncidentSource  $incidentSource
     * @return IncidentSourceResource|JsonResponse
     */
    public function updateIncidentSource(
        IncidentSourceRequest $request,
        IncidentSource $incidentSource
    ): IncidentSourceResource|JsonResponse {
        try {
            $validatedData = $request->validated();
            $validatedData['model_type'] = match ($validatedData['model_type']) {
                'salons' => 'App\Models\Salon',
                'production_lines' => 'App\Models\ProductionLine',
                'stations' => 'App\Models\Station',
            };
            $data = $this->incidentSourceRepository->update($incidentSource->id, $validatedData);
            return new IncidentSourceResource($data);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function listIncidentSource(): AnonymousResourceCollection
    {
        $data = $this->incidentSourceRepository->paginate(request()->input('paginate', 15));

        return IncidentSourceResource::collection($data);
    }

}
