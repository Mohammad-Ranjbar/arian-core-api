<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGradeRequest;
use App\Http\Requests\UpdateQcStatusRequest;
use App\Http\Resources\VehicleQcResource;
use App\Http\Resources\VehicleResource;
use App\Jobs\ExportJob;
use App\Models\ActionLog;
use App\Models\Job;
use App\Models\Vehicle;
use App\Repositories\ActionLogRepository;
use App\Repositories\CarModelRepository;
use App\Repositories\MakeCodeRepository;
use App\Repositories\VehicleRepository;
use App\Services\Erp\ErpGetVehicle;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class VehicleController extends CrudController
{

    /**
     * @param MakeCodeRepository $makeCodeRepository
     * @param CarModelRepository $carModelRepository
     * @param VehicleRepository $vehicleRepository
     * @param ErpGetVehicle $erpGetVehicle
     * @param ActionLogRepository $actionLogRepository
     */
    public function __construct(
        private readonly MakeCodeRepository  $makeCodeRepository,
        private readonly CarModelRepository  $carModelRepository,
        private readonly VehicleRepository   $vehicleRepository,
        private readonly ErpGetVehicle       $erpGetVehicle,
        private readonly ActionLogRepository $actionLogRepository
    )
    {

    }


    /**
     * @param string $vin
     * @return JsonResponse|VehicleResource
     * @throws ValidationException
     */
    public function getByVin(string $vin): JsonResponse|VehicleResource
    {
        $vehicle = $this->getData($vin);

        if (Gate::allows('access', $vehicle)) {
            return new VehicleResource($vehicle);
        } else {
            return new JsonResponse(
                data: ['message' => 'You are not authorized to access this vehicle'],
                status: Response::HTTP_FORBIDDEN
            );
        }

    }

    /**
     * @param string $vin
     * @return Model|Builder
     * @throws ValidationException
     */
    public function getData(string $vin): Model|Builder
    {
        $vehicleData = $this->erpGetVehicle->get($vin);
        $makeCode = $this->makeCodeRepository->make($vehicleData['make_code']);
        $carModel = $this->carModelRepository->make($vehicleData['car_model']);

        $data = [
            'vin' => $vehicleData['vin'],
            'color_code' => $vehicleData['color_code'],
            'production_order_number' => $vehicleData['production_order_number'],
            'engine_no' => $vehicleData['engine_no'], 'cabin_no' => $vehicleData['cabin_no'],
            'make_code_id' => $makeCode->getAttribute('id'),
            'car_model_id' => $carModel->getAttribute('id'),
        ];


        return $this->vehicleRepository->make($data, 'vin');
    }

    /**
     * @param UpdateQcStatusRequest $request
     * @param Vehicle $vehicle
     * @return JsonResponse|VehicleResource
     */
    public function updateQcStatus(
        UpdateQcStatusRequest $request,
        Vehicle               $vehicle
    ): JsonResponse|VehicleResource
    {
        try {
            $validatedData = $request->validated();
            $response = $this->updateStatus($vehicle->getAttribute('vin'), $validatedData['qc_status']);

            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 400);
            }
            ActionLog::query()->create([
                'model_type' => Vehicle::class,
                'model_id' => $vehicle->getAttribute('vin'),
                'action' => 'update_qc_status',
                'user_id' => Auth::id(),
            ]);
            $this->vehicleRepository->update($vehicle->vin, $validatedData);
            return new VehicleResource($vehicle);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    /**
     * @param string $vin
     * @param string $qcStatus
     * @return array
     */
    private function updateStatus(string $vin, string $qcStatus): array
    {
        return ErpWsdlClient::run(config('erp.update_qc_status'), 'SetQCStatusCommercialProducts',
            ['vINP' => $vin, 'qCStatusP' => $qcStatus]);

    }

    /**
     * @param UpdateGradeRequest $request
     * @param Vehicle $vehicle
     * @return JsonResponse|VehicleResource
     */
    public function updateGradeStatus(
        UpdateGradeRequest $request,
        Vehicle            $vehicle
    ): JsonResponse|VehicleResource
    {
        try {
            $validatedData = $request->validated();
            $this->vehicleRepository->update($vehicle->vin, $validatedData);
            return new VehicleResource($vehicle);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function reportVehicleQc(Request $request): JsonResponse
    {
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'export-vehicle-qc';
        $validatedData['data'] = $request->toArray();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));
        return response()->json([
            'message' => 'export job created', 'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

    /**
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function vehicleQc(): JsonResponse|AnonymousResourceCollection
    {
        try {
            $data = $this->actionLogRepository
                ->where('action', ActionLog::UPDATED_QC_STATUS)->paginate(request()->per_page);

            return VehicleQcResource::collection($data);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }

    }


}
