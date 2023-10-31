<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleResource;
use App\Repositories\CarModelRepository;
use App\Repositories\MakeCodeRepository;
use App\Repositories\VehicleRepository;
use App\Services\HttpServiceTrait;
use Illuminate\Http\Request;

class VehicleController extends CrudController
{
    use HttpServiceTrait;

    private MakeCodeRepository $makeCodeRepository;
    private CarModelRepository $carModelRepository;
    private VehicleRepository  $vehicleRepository;

    public function __construct(
        MakeCodeRepository $makeCodeRepository,
        CarModelRepository $carModelRepository,
        VehicleRepository $vehicleRepository
    ) {
        $this->makeCodeRepository = $makeCodeRepository;
        $this->carModelRepository = $carModelRepository;
        $this->vehicleRepository = $vehicleRepository;
    }

    public function getByVin(Request $request, $vin)
    {
        $obj = $this->getVehicleByVin($vin);
        $makeCode = $this->makeCodeRepository->make($obj['make_code']);
        $carModel = $this->carModelRepository->make($obj['car_model']);
        $data = [
            'vin'                     => $obj['vin'],
            'production_order_number' => $obj['production_order_number'],
            'engine_no'               => $obj['engine_no'],
            'cabin_no'                => $obj['cabin_no'],
            'make_code_id'            => $makeCode->id,
            'car_model_id'            => $carModel->id,
        ];
        $vehicle = $this->vehicleRepository->make($data,'vin');

        $user = $request->user();
        if ($user->makeCodes()->where('make_codes.id', $makeCode->id)->exists()) {
            return new VehicleResource($vehicle);
        }
        abort(403);
    }

}
