<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\Erp\ErpGetVehicleLastLocation;
use Exception;

class LastLocationVehicleController extends Controller
{

    public function __invoke(Vehicle $vehicle)
    {
        try {
            $response = resolve(ErpGetVehicleLastLocation::class)->get(
                vin: $vehicle->getAttribute('vin'),
                carModelReference: $vehicle->carModel->getAttribute('reference'),
            );
            return response()->json($response);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage());
        }
    }
}
