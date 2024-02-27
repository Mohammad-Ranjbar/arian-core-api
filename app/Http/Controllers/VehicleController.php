<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGradeRequest;
use App\Http\Requests\UpdateQcStatusRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Repositories\CarModelRepository;
use App\Repositories\MakeCodeRepository;
use App\Repositories\VehicleRepository;
use App\Services\Erp\ErpGetVehicle;
use App\Services\Erp\ErpWsdlClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class VehicleController extends CrudController
{
    /**
     * @param  MakeCodeRepository  $makeCodeRepository
     * @param  CarModelRepository  $carModelRepository
     * @param  VehicleRepository  $vehicleRepository
     * @param  ErpGetVehicle  $erpGetVehicle
     */
    public function __construct(
        private readonly MakeCodeRepository $makeCodeRepository,
        private readonly CarModelRepository $carModelRepository,
        private readonly VehicleRepository $vehicleRepository,
        private readonly ErpGetVehicle $erpGetVehicle
    ) {

    }

    /**
     * @param  string  $vin
     * @return JsonResponse|VehicleResource
     * @throws ValidationException
     */
    public function getByVin(string $vin): JsonResponse|VehicleResource
    {
        $vehicleData = $this->erpGetVehicle->get($vin);
        $makeCode = $this->makeCodeRepository->make($vehicleData['make_code']);
        $carModel = $this->carModelRepository->make($vehicleData['car_model']);
        $data = [
            'vin'                     => $vehicleData['vin'],
            'production_order_number' => $vehicleData['production_order_number'],
            'engine_no'               => $vehicleData['engine_no'], 'cabin_no' => $vehicleData['cabin_no'],
            'make_code_id'            => $makeCode->getAttribute('id'),
            'car_model_id'            => $carModel->getAttribute('id'),
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
     * @param  UpdateQcStatusRequest  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|VehicleResource
     */
    public function updateQcStatus(
        UpdateQcStatusRequest $request,
        Vehicle $vehicle
    ): JsonResponse|VehicleResource {
        try {
            $validatedData = $request->validated();
            $response = $this->updateStatus($vehicle->getAttribute('vin'), $validatedData['qc_status']);

            if ($response['status'] !== 'ok') {
                return $this->responseError($response['message'], 400);
            }
            $this->vehicleRepository->update($vehicle->vin, $validatedData);
            return new VehicleResource($vehicle);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    /**
     * @param  UpdateGradeRequest  $request
     * @param  Vehicle  $vehicle
     * @return JsonResponse|VehicleResource
     */
    public function updateGradeStatus(
        UpdateGradeRequest $request,
        Vehicle $vehicle
    ): JsonResponse|VehicleResource {
        try {
            $validatedData = $request->validated();
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
}
