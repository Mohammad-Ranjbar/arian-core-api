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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends CrudController
{
    use HttpServiceTrait;

    public function __construct(
        private readonly MakeCodeRepository $makeCodeRepository,
        private readonly CarModelRepository $carModelRepository,
        private readonly VehicleRepository $vehicleRepository
    ) {

    }

    public function getByVin(Request $request, $vin)
    {
        $obj = $this->getVehicleByVin($vin);
        $makeCode = $this->makeCodeRepository->make($obj['make_code']);
        $carModel = $this->carModelRepository->make($obj['car_model']);
        $data = [
            'vin'          => $obj['vin'], 'production_order_number' => $obj['production_order_number'],
            'engine_no'    => $obj['engine_no'], 'cabin_no' => $obj['cabin_no'], 'make_code_id' => $makeCode->id,
            'car_model_id' => $carModel->id,
        ];
        $vehicle = $this->vehicleRepository->make($data, 'vin');

        $user = $request->user();
        if ($user->makeCodes()->where('make_codes.id', $makeCode->id)->exists()) {
            return new VehicleResource($vehicle);
        }
        abort(403);
    }

    public function updateQcStatus(
        UpdateQcStatusRequest $request,
        Vehicle $vehicle
    ): JsonResponse|VehicleResource {
        try {
            $validatedData = $request->validated();
            $this->vehicleRepository->update($vehicle->vin, $validatedData);
            return new VehicleResource($vehicle);
        } catch (Exception $exception) {
            return response()->json([
                'message' => 'cant update qc status'
            ], 500);
        }
    }
    public function updateGradeStatus(
        UpdateGradeRequest $request,
        Vehicle $vehicle
    ): JsonResponse|VehicleResource {
        try {
            $validatedData = $request->validated();
            $this->vehicleRepository->update($vehicle->vin, $validatedData);
            return new VehicleResource($vehicle);
        } catch (Exception $exception) {
            return response()->json([
                'message' => 'cant update qc status'
            ], 500);
        }
    }
}
