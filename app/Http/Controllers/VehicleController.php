<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGradeRequest;
use App\Http\Requests\UpdateQcStatusRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Repositories\CarModelRepository;
use App\Repositories\MakeCodeRepository;
use App\Repositories\VehicleRepository;
use App\Services\HttpServiceTrait;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class VehicleController extends CrudController
{
    use HttpServiceTrait;

    /**
     * @param  MakeCodeRepository  $makeCodeRepository
     * @param  CarModelRepository  $carModelRepository
     * @param  VehicleRepository  $vehicleRepository
     */
    public function __construct(
        private readonly MakeCodeRepository $makeCodeRepository,
        private readonly CarModelRepository $carModelRepository,
        private readonly VehicleRepository $vehicleRepository
    ) {

    }


    /**
     * @param $vin
     * @return VehicleResource
     * @throws AuthorizationException
     */
    public function getByVin($vin): VehicleResource
    {
        $obj = $this->getVehicleByVin($vin);
        $makeCode = $this->makeCodeRepository->make($obj['make_code']);
        $carModel = $this->carModelRepository->make($obj['car_model']);
        $data = [
            'vin'                     => $obj['vin'],
            'production_order_number' => $obj['production_order_number'],
            'engine_no'               => $obj['engine_no'], 'cabin_no' => $obj['cabin_no'],
            'make_code_id'            => $makeCode->getAttribute('id'),
            'car_model_id'            => $carModel->getAttribute('id'),
        ];
        $vehicle = $this->vehicleRepository->make($data, 'vin');
        $this->authorize('access', $vehicle);
        return new VehicleResource($vehicle);
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
}
