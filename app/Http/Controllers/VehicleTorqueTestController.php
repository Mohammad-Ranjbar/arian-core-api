<?php

namespace App\Http\Controllers;

use App\Http\Requests\RepeatVehicleTorqueTestRequest;
use App\Http\Resources\VehicleTorqueTestResource;
use App\Models\VehicleTorqueTest;


class VehicleTorqueTestController extends Controller
{

    public function __invoke(VehicleTorqueTest $vehicleTorqueTest, RepeatVehicleTorqueTestRequest $request)
    {

        $validatedData = $request->validated();
        $validatedData['screw_id'] = $vehicleTorqueTest->getAttribute('screw_id');

        $data = $vehicleTorqueTest->childTests()->create($validatedData);

        $vehicleTorqueTest->load('childTests');

        return VehicleTorqueTestResource::make($vehicleTorqueTest);
    }
}
