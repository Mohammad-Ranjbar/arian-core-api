<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaultReportExcelRequest;
use App\Http\Requests\IncidentSourceRequest;
use App\Http\Requests\VehicleFaultRequest;
use App\Http\Resources\IncidentSourceResource;
use App\Http\Resources\VehicleIncidentResource;
use App\Jobs\ExportJob;
use App\Models\IncidentSource;
use App\Models\IncidentSourceSelection;
use App\Models\Job;
use App\Models\Location;
use App\Models\Station;
use App\Models\Vehicle;
use App\Models\VehicleIncident;
use App\Models\WorkGroupCenter;
use App\Repositories\IncidentSourceRepository;
use App\Repositories\VehicleIncidentRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        unset($vehicle);
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
                ->where('vin_type', $vehicle->getAttribute('data')['ItemType'])
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
            $validatedData['vin_type'] = $vehicle->getAttribute('data')['ItemType'];
            $validatedData['production_type'] = $vehicle->getProductionType();
            return new VehicleIncidentResource($this->repository->store($validatedData));
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }

    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
        $lock = Cache::lock('incident-source-update:user:'.$request->user()->getAuthIdentifier(), 30);
        if (! $lock->get()) {
            return response()->json(['message' => 'درخواست قبلی شما برای ثبت منشأ ایراد در حال پردازش است. لطفاً صبر کنید.'], 429);
        }

        try {
            $data = DB::transaction(function () use ($request, $incidentSource) {
                $source = IncidentSource::query()->lockForUpdate()->findOrFail($incidentSource->id);
                $selections = $source->selections()->get();
                $keep = [];
                foreach ($request->validated('sources') as $item) {
                    $model = new (IncidentSourceSelection::MODEL_TYPES[$item['model_type']]);
                    $type = $model->getMorphClass();
                    $selection = $selections->first(fn ($selection) => $selection->model_type === $type
                        && $selection->model_id === (int) $item['model_id']);
                    $selection ??= $source->selections()->create([
                        'model_type' => $type,
                        'model_id' => $item['model_id'],
                        'user_id' => $request->user()->getAuthIdentifier(),
                    ]);
                    $keep[] = $selection->id;
                }
                $source->selections()->whereNotIn('id', $keep)->delete();
                // Keep the legacy single-selection columns aligned for existing consumers.
                $first = $source->selections()->first();
                $source->update(['model_type' => $first?->model_type, 'model_id' => $first?->model_id]);

                return $source->load(IncidentSource::resourceRelations());
            });

            return new IncidentSourceResource($data);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  IncidentSource  $incidentSource
     * @return IncidentSourceResource
     */
    public function showIncidentSource(IncidentSource $incidentSource): IncidentSourceResource
    {
        return new IncidentSourceResource($incidentSource->loadMissing(IncidentSource::resourceRelations()));
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function listIncidentSource(): JsonResponse|AnonymousResourceCollection
    {
        return $this->incidentSourcesFor();
    }

    public function locationIncidentSources(Location $location): AnonymousResourceCollection
    {
        return $this->incidentSourcesFor(['model_type' => 'locations', 'model_id' => $location->getKey()]);
    }

    public function workGroupCenterIncidentSources(WorkGroupCenter $workGroupCenter): AnonymousResourceCollection
    {
        return $this->incidentSourcesFor(['model_type' => 'work_group_centers', 'model_id' => $workGroupCenter->getKey()]);
    }

    private function incidentSourcesFor(array $routeFilters = []): AnonymousResourceCollection
    {
        $validated = request()->validate([
            'model_type' => ['sometimes', 'bail', 'string', Rule::in(array_keys(IncidentSourceSelection::MODEL_TYPES))],
            'model_id' => ['sometimes', 'bail', 'integer', 'min:1'],
            'paginate' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ], [
            'model_type.string' => 'نوع منشأ ایراد باید رشتهٔ متنی باشد.',
            'model_type.in' => 'نوع منشأ ایراد باید مرکز گروه کاری یا موقعیت باشد.',
            'model_id.integer' => 'شناسهٔ منشأ ایراد باید عدد صحیح باشد.',
            'model_id.min' => 'شناسهٔ منشأ ایراد باید بزرگ‌تر از صفر باشد.',
            'paginate.integer' => 'تعداد رکورد در هر صفحه باید عدد صحیح باشد.',
            'paginate.min' => 'تعداد رکورد در هر صفحه باید حداقل ۱ باشد.',
            'paginate.max' => 'تعداد رکورد در هر صفحه نباید بیشتر از ۱۰۰ باشد.',
            'page.integer' => 'شمارهٔ صفحه باید عدد صحیح باشد.',
            'page.min' => 'شمارهٔ صفحه باید حداقل ۱ باشد.',
        ]);

        return IncidentSourceResource::collection(
            $this->incidentSourceRepository->paginateForResource((int) ($validated['paginate'] ?? 15), array_replace($validated, $routeFilters))
        );
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

        $where [] = "vehicle_incidents.deleted_at is null";
        $where [] = "vehicle_incidents.type = '".request()->route()->parameter('type')."'";

        if (request()->get('date_type') === 'create') {
            $where[] = "vehicle_incidents.created_at BETWEEN '$fromDate' AND '$toDate'";
            $dateField = 'vehicle_incidents.created_at';
        } else {
            $where[] = "vehicle_incidents.checked_at BETWEEN '$fromDate' AND '$toDate'";
            $dateField = 'vehicle_incidents.checked_at';
        }
        if (!empty(request()->get('car_model'))) {
            $where[] = "vehicles.car_model_id in (".implode(',', request()->get('car_model')).")";
        }
        if (!empty(request()->get('incident_category_id'))) {
            $where[] = "vehicle_incidents.incident_category_id = ". request()->get('incident_category_id');
        }
        if (!empty(request()->get('make_code'))) {
            $where[] = "vehicles.make_code_id in (".implode(',', request()->get('make_code')).")";
        }

        if (!empty(request()->get('intensities'))) {
            $l = collect((array) request()->get('intensities'))->map(function ($item) {
                return "'$item'";
            })->implode(',');
            $where[] = "vehicle_incidents.intensity in ($l)";
        }

        if (!empty(request()->get('method'))) {
            $l = collect((array) request()->get('method'))->map(function ($item) {
                return "'$item'";
            })->implode(',');
            $where[] = "vehicle_incidents.method in ($l)";
        }
        if (!empty(request()->get('vin_type'))) {
            $l = collect((array) request()->get('vin_type'))->map(function ($item) {
                return "'$item'";
            })->implode(',');
            $where[] = "vehicle_incidents.vin_type in ($l)";
        }

        if (!empty(request()->get('custom_location_id'))) {

            $l = collect((array) request()->get('custom_location_id'))->map(function ($item) {
                return "'$item'";
            })->implode(',');
            $where[] = "vehicle_incidents.custom_location_id in ($l)";
        }
        if (!empty(request()->get('custom_location_type'))) {
            $location = null;
            if (request()->route()->parameter('type') == 'evaluation') {
                $location = Location::class;
            } elseif (request()->route()->parameter('type') == 'qc') {
                $location = Station::class;
            }
            if ($location) {
                $where[] = "vehicle_incidents.custom_location_type = '".addslashes($location)."'";
            }
        }

        if (!empty(request()->get('element_id'))) {
            $where[] = "vehicle_incidents.element_id in (".implode(',', request()->get('element_id')).")";
        }

        if (!empty(request()->get('production_type'))) {

            $l = collect((array) request()->get('production_type'))->map(function ($item) {
                return "'$item'";
            })->implode(',');
            $where[] = "vehicle_incidents.production_type in ($l)";
        }

        if (!empty(request()->get('incident_id'))) {
            $where[] = "vehicle_incidents.incident_id in (".implode(',', request()->get('incident_id')).")";
        }


        $strWhere = implode(" AND ", $where);

        $result = collect(DB::select(/** @lang text */ "
            SELECT
                DATE($dateField) as date,
                count(distinct vehicle_incidents.vin, 2) as vehicles,
                SUM(incidents.weight) as incidents,
                TRUNCATE(SUM(incidents.weight) / count(distinct vehicle_incidents.vin), 2) as dpuoff
            FROM
               vehicle_incidents
            left join
                vehicles
            on
                vehicle_incidents.vin = vehicles.vin
            left join
                incidents
            on
                incidents.id = vehicle_incidents.incident_id
            WHERE
                $strWhere
            GROUP BY
                DATE($dateField)
       "));


        $vehicleCount = collect(DB::select(/** @lang text */ "
            SELECT
                count(DISTINCT vehicle_incidents.vin) as vehicleCount
            FROM
               vehicle_incidents
            left join
                vehicles
            on
                vehicle_incidents.vin = vehicles.vin
            left join
                incidents
            on
                incidents.id = vehicle_incidents.incident_id
            WHERE
                $strWhere
       "))->sum('vehicleCount');


        $labels = $result->pluck('date')->all();

        $datasets = [
            [
                'label' => "Vehicles",
                'data'  => $result->pluck('vehicles')->all(),
                'backgroundColor' => '#000000',
                'borderColor' => '#000000',
            ],
            [
                'label' => "Incidents",
                'data'  => $result->pluck('incidents')->all(),
                'backgroundColor' => '#F7FF00',
                'borderColor' => '#F7FF00',
            ],
            [
                'label' => "DPU-OFF",
                'data'  => $result->pluck('dpuoff')->all(),
                'backgroundColor' => '#2f54EB',
                'borderColor' => '#2f54EB',
            ]
        ];

        if (request()->route()->parameter('type') === 'evaluation') {
            foreach (['method', 'intensity'] as $column) {
                $temp = collect(DB::select(/** @lang text */ "
            SELECT
                DATE($dateField) as `date`,
                SUM(incidents.weight) as count,
                `$column`
            FROM
                vehicle_incidents
            left join
                vehicles
            on
                vehicle_incidents.vin = vehicles.vin
            left join
                incidents
            on
                incidents.id = vehicle_incidents.incident_id
            WHERE
                $strWhere
            GROUP BY
                DATE($dateField), `$column`
        "));
                $colors = [
                    'V1' => '#FB2C36',
                    '(V1)' => '#FB2C36',
                    'V2' => '#FE9A00',
                    '(V2)' => '#FE9A00',
                    'V3' => '#FFDF20',
                    '(V3)' => '#FFDF20',
                    'V1+' => '#82181A',
                    'SAVES' => '#7900FF',
                ];
                foreach ($temp->groupBy($column) as $value => $items) {
                    $x = $items->pluck('count', 'date');
                    $dataSet = [];
                    foreach ($labels as $date) {
                        $dataSet[$date] = isset($x[$date]) ? $x[$date] : 0;
                    }
                    $color = $colors[$value] ?? '#757575';
                    $datasets[] = ['label' => $value, 'data' => array_values($dataSet), 'backgroundColor' => $color,
                        'borderColor' => $color,];
                }
            }
        }

        $sql = /** @lang text */
            "
            SELECT
                concat(incidents.title, ' ' ,elements.title) as title,
                concat(incidents.title, ' ' ,incidents.code) as incident_title,
                concat(elements.title, ' ' ,elements.code) as element_title,
                GROUP_CONCAT(DISTINCT vehicle_incidents.description) as description,
                count(vehicle_incidents.incident_id) as `count`
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
                COUNT(*) DESC
       ";

        $list = collect(DB::select($sql));

        return response()->json([
            'avg_dpu_off' => $result->avg('dpuoff'),
            'dpu_off'     => $vehicleCount > 0 ? number_format($result->sum('incidents') / $vehicleCount , 2): 0,
            'vehicles'    => $vehicleCount,
            'incidents'   => $result->sum('incidents'),
            'list'        => $list->all(),
            'chart'       => [
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
