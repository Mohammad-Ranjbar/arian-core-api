<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentSourceRequest;

use App\Http\Requests\VehicleFaultRequest;
use App\Http\Resources\IncidentSourceResource;
use App\Http\Resources\VehicleIncidentResource;
use App\Models\Incident;
use App\Models\IncidentSource;
use App\Models\Vehicle;
use App\Repositories\IncidentSourceRepository;
use App\Repositories\VehicleIncidentRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;


class VehicleFaultController extends Controller
{

    /**
     * @param VehicleIncidentRepository $repository
     * @param IncidentSourceRepository $incidentSourceRepository
     */
    public function __construct(private readonly VehicleIncidentRepository $repository, private readonly IncidentSourceRepository $incidentSourceRepository)
    {

    }

    /**
     * @param VehicleFaultRequest $request
     * @param Vehicle $vehicle
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
            $validatedData['production_tracking_id'] = $tracking->getAttribute('id');
            $this->incidentSourceRepository->upsert(['incident_id' => $validatedData['incident_id'], 'element_id' => $validatedData['element_id'], 'car_model_id' => $vehicle->carModel->id,]);
            return new VehicleIncidentResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }

    }

    /**
     * @param Vehicle $vehicle
     * @param $type
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Vehicle $vehicle, $type): JsonResponse|AnonymousResourceCollection
    {
        $this->authorize("vehicles.faults.$type.index");

        request()->merge(['type' => request()->route()->parameter('type'), 'vin' => $vehicle->vin,]);

        $perPage = request()->input('per_page', 15);
        return VehicleIncidentResource::collection($this->repository->paginate($perPage));

    }

    /**
     * @param IncidentSourceRequest $request
     * @param IncidentSource $incidentSource
     * @return IncidentSourceResource|JsonResponse
     */
    public function updateIncidentSource(IncidentSourceRequest $request, IncidentSource $incidentSource): IncidentSourceResource|JsonResponse
    {
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
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    /**
     * @param IncidentSource $incidentSource
     * @return IncidentSourceResource
     */
    public function showIncidentSource(IncidentSource $incidentSource): IncidentSourceResource
    {
        return new IncidentSourceResource($incidentSource);
    }


    /**
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function listIncidentSource(): JsonResponse|AnonymousResourceCollection
    {
        return IncidentSourceResource::collection($this->incidentSourceRepository->paginate(request()->input('paginate', 15)));
    }




    public function report(): JsonResponse
    {
        $this->authorize('vehicles.faults.' . request()->route()->parameter('type') . '.report');


$countByDay = DB::select(/** @lang text */ "SELECT DATE(created_at) as date,
                        count(distinct vin) as vehicles,
                        count(distinct `method`) as `methods`,
                        COUNT(*) as incidents,
                        TRUNCATE(COUNT(*) / count(distinct vin), 2) as dpuoff
                           FROM vehicle_incidents
                           GROUP BY DATE(created_at)");


//$countByVin = DB::select("SELECT count(distinct vin) as count
//                          FROM vehicle_incidents
//                          ");

//        dd($countByDay);
        return response()->json(['dpu_off' => 5.2,'vehicles' => 5, 'list' => [['title' => 'the', 'count' => 5], ['title' => 'the', 'count' => 4], ['title' => 'the', 'count' => 2]], 'chart' => ['labels' => ["2016", "2017", "2018", "2019", "2020"], 'datasets' => [['label' => "Sales", 'data' => [80000, 45677, 78888, 90000, 4300], 'backgroundColor' => "rgb(24, 229, 189)",], ['label' => "Revenue", 'data' => [3000, 60000, 70000, 5000, 80000], 'backgroundColor' => "rgb(24, 167, 229)",],],]]);
    }

}
