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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleFaultController extends Controller
{

    /**
     * @param  VehicleIncidentRepository  $repository
     * @param  IncidentSourceRepository  $incidentSourceRepository
     */
    public function __construct(
        private readonly VehicleIncidentRepository $repository,
        private readonly IncidentSourceRepository $incidentSourceRepository
    ) {

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
            $tracking = $vehicle->tracking()->latest()->first();
            if (!$tracking) {
                return $this->responseError('can not find vehicle location');
            }
            $validatedData['production_tracking_id'] = $tracking->id;
            $this->incidentSourceRepository->upsert([
                'incident_id'  => $validatedData['incident_id'], 'element_id' => $validatedData['element_id'],
                'car_model_id' => $vehicle->carModel->id,
            ]);
            return new VehicleIncidentResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(),500);
        }

    }

    /**
     * @param  Vehicle  $vehicle
     * @param $type
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        Vehicle $vehicle,
        $type
    ): JsonResponse|AnonymousResourceCollection {
            $this->authorize("vehicles.faults.$type.index");

            request()->merge([
                'type' => request()->route()->parameter('type'), 'vin' => $vehicle->vin,
            ]);

            $perPage = request()->input('per_page', 15);
            return VehicleIncidentResource::collection($this->repository->paginate($perPage));

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
            return $this->responseError($exception->getMessage(),500);
        }
    }


    /**
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function listIncidentSource(): JsonResponse|AnonymousResourceCollection
    {
        $data = $this->incidentSourceRepository->paginate(request()->input('paginate', 15));
        if ($data->count() === 0) {
            return $this->responseError('there is no incident source', 404);
        }
        return IncidentSourceResource::collection($data);
    }

}
