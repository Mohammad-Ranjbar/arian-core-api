<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGradeRequest;
use App\Http\Requests\UpdateQcStatusRequest;
use App\Http\Requests\VehicleQcRequest;
use App\Http\Resources\VehicleQcResource;
use App\Http\Resources\VehicleResource;
use App\Jobs\ExportJob;
use App\Models\ActionLog;
use App\Models\Job;
use App\Models\Vehicle;
use App\Repositories\CarModelRepository;
use App\Repositories\MakeCodeRepository;
use App\Repositories\VehicleRepository;
use App\Services\Erp\ErpGetVehicle;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     */
    public function __construct(
        private readonly MakeCodeRepository $makeCodeRepository,
        private readonly CarModelRepository $carModelRepository,
        private readonly VehicleRepository  $vehicleRepository,
        private readonly ErpGetVehicle      $erpGetVehicle
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
        $vehicleData = $this->erpGetVehicle->get($vin);
        $makeCode = $this->makeCodeRepository->make($vehicleData['make_code']);
        $carModel = $this->carModelRepository->make($vehicleData['car_model']);
        $data = [
            'vin' => $vehicleData['vin'],
            'production_order_number' => $vehicleData['production_order_number'],
            'engine_no' => $vehicleData['engine_no'], 'cabin_no' => $vehicleData['cabin_no'],
            'make_code_id' => $makeCode->getAttribute('id'),
            'car_model_id' => $carModel->getAttribute('id'),
        ];

        $vehicle = $this->vehicleRepository->make($data, 'vin');

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
//            $response = $this->updateStatus($vehicle->getAttribute('vin'), $validatedData['qc_status']);

//            if ($response['status'] !== 'ok') {
//                return $this->responseError($response['message'], 400);
//            }
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

    public function vehicleQc(VehicleQcRequest $request)
    {
        $validatedData = $request->validated();
//        if (!isset($validatedData['created_at_from'])) {
//            $validatedData['created_at_from'] = now()->subCentury();
//        }
//        if (!isset($validatedData['created_at_to'])) {
//            $validatedData['created_at_to'] = now()->addCentury();
//        }
        $data = ActionLog::query()->where('action',ActionLog::UPDATED_QC_STATUS)
            ->where('model_type', Vehicle::class)
//            ->where('created_at', '>=', $validatedData['created_at_from'])
//            ->where('created_at', '<=', $validatedData['created_at_to'])
            ->whereHasMorph('model', [Vehicle::class], function ($query) use ($validatedData) {
//                $query->where('make_code_id', $validatedData['make_code_id'])
//                    ->where('car_model_id', $validatedData['car_model_id']);
            })->paginate();

        return VehicleQcResource::collection($data);
    }


}
