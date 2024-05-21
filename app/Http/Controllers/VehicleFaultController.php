<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaultReportExcelRequest;
use App\Http\Requests\IncidentSourceRequest;
use App\Http\Requests\VehicleFaultRequest;
use App\Http\Resources\IncidentSourceResource;
use App\Http\Resources\VehicleIncidentResource;
use App\Jobs\ExportJob;
use App\Models\IncidentSource;
use App\Models\Job;
use App\Models\Vehicle;
use App\Models\VehicleIncident;
use App\Repositories\IncidentSourceRepository;
use App\Repositories\VehicleIncidentRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


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

    public function destroy(Vehicle $vehicle, $id): JsonResponse
    {
        $this->repository->destroy($id);

        return response()->json(['message' => 'ایراد مورد نظر با موفقیت حذف گردید']);
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
            $checking = VehicleIncident::query()
                ->where('vin', $vehicle->getAttribute('vin'))
                ->where('element_id', $validatedData['element_id'])
                ->where('incident_id', $validatedData['incident_id'])
                ->where('type', $validatedData['type'])
                ->whereNull('deleted_at')
                ->exists();

            if ($checking) {
                return $this->responseError('برای خودرو مورد نظر ایراد و قطعه انتخابی موجود است');
            }
            $validatedData['production_tracking_id'] = $tracking->getAttribute('id');
            $validatedData['custom_location_id'] = $validatedData['location']['custom_location_id'];
            $validatedData['custom_location_type'] = $validatedData['location']['custom_location_type'];
            $this->incidentSourceRepository->upsert([
                'incident_id'  => $validatedData['incident_id'],
                'element_id'   => $validatedData['element_id'],
                'car_model_id' => $vehicle->carModel->id,
            ]);
            return new VehicleIncidentResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }

    }

    /**
     * @param  Vehicle  $vehicle
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
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    /**
     * @param  IncidentSource  $incidentSource
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
        return IncidentSourceResource::collection($this->incidentSourceRepository->paginate(request()->input('paginate',
            15)));
    }


    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function report(): JsonResponse
    {
        $this->authorize('vehicles.faults.'.request()->route()->parameter('type').'.report');


        $fromDate = request()->get('from') ?? now()->subCentury()->toDateTimeString();
        $toDate = request()->get('to') ?? now()->addCentury()->toDateTimeString();
        $where [] = 1;
        if (request()->get('date_type') === 'create') {
            $where[] = "vehicle_incidents.created_at BETWEEN '$fromDate' AND '$toDate'";
            $dateField = 'created_at';
        } else {
            $where[] = "vehicle_incidents.checked_at BETWEEN '$fromDate' AND '$toDate'";
            $dateField = 'checked_at';
        }

        $where[] = "vehicles.car_model_id in (".implode(',', request()->get('car_model')).")";
        if (!empty(request()->get('make_code'))) {
            $where[] = "vehicles.make_code_id in (".implode(',', request()->get('make_code')).")";
        }


        $strWhere = implode(" AND ", $where);

        $result = collect(DB::select(/** @lang text */ "
            SELECT
                DATE({$dateField}) as date,
                count(distinct vin) as vehicles,
                COUNT(*) as incidents,
                TRUNCATE(COUNT(*) / count(distinct vin), 2) as dpuoff
            FROM
               vehicle_incidents
            left join
                vehicles
            on
                vehicle_incidents.vin = vehicles.vin
            WHERE
                $strWhere
            GROUP BY
                DATE($dateField)
       "));


        $labels = $result->pluck('date')->all();

        $datasets = [
            [
                'label' => "Vehicles",
                'data'  => $result->pluck('vehicles')->all(),
            ],
            [
                'label' => "Incidents",
                'data'  => $result->pluck('incidents')->all(),
            ],
            [
                'label' => "DPU-OFF",
                'data'  => $result->pluck('dpuoff')->all(),
            ]
        ];

        foreach (['method', 'intensity'] as $column) {
            $temp = collect(DB::select(/** @lang text */ "
            SELECT
                DATE($dateField) as `date`,
                COUNT(*) as count,
                `$column`
            FROM
                vehicle_incidents
            left join
                vehicles
            on
                vehicle_incidents.vin = vehicles.vin                
            WHERE
                $strWhere
            GROUP BY
                DATE($dateField), `$column`
        "));

            foreach ($temp->groupBy($column) as $value => $items) {
                $x = $items->pluck('count', 'date');
                $dataSet = [];
                foreach ($labels as $date) {
                    $dataSet[$date] = isset($x[$date]) ? $x[$date] : 0;
                }

                $datasets[] = ['label' => $value, 'data' => array_values($dataSet),];
            }
        }

        $list = collect(DB::select(/** @lang text */ "
            SELECT
                concat(incidents.title, elements.title) as title,
                count(incident_id) as `count`
            FROM
               vehicle_incidents
            left join
                vehicles
            on
                vehicle_incidents.vin = vehicles.vin
            left join
                   incidents
            on
                vehicle_incidents.incident_id = incidents.id
            left join
                   elements
            on
                vehicle_incidents.element_id = elements.id
            WHERE
                $strWhere
            GROUP BY
                incident_id, element_id
            ORDER BY
                COUNT(incident_id) DESC
       "));

        return response()->json([
            'dpu_off'  => $result->avg('dpuoff'),
            'vehicles' => $result->sum('vehicles'),
            'list'     => $list->all(),
            'chart'    => [
                'labels'   => $labels,
                'datasets' => $datasets
            ]
        ]);
    }


    public function reportExcel(FaultReportExcelRequest $request): JsonResponse
    {

        $validatedData['type'] = 'export-faults-'.$request->validated('type');
        $validatedData['user_id'] = $request->validated('user_id');
        $validatedData['data'] = $request->validated();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'export job created',
            'data'    => [
                'uuid'         => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

}
