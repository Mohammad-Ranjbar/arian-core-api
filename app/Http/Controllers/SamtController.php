<?php

namespace App\Http\Controllers;

use App\Http\Requests\SamtRequest;
use App\Models\DWVehicleServiceHistoryTree;

class SamtController extends Controller
{

    public function __invoke(SamtRequest $request)
    {
        $validatedData = $request->validated();

        $vehicle = DwVehicleServiceHistoryTree::query()->latest('create_date')->first()->toArray();
        dd($vehicle);
        $vehicleInfo = DWVehicleServiceHistoryTree::query()->where('vin', $validatedData['VinNO'])->first();

        $result = [
            'resultCode' => 1,
            'resultMessage' => '',
            'Obj' => $this->obj($vehicle)
        ];
    }


    private function obj(array $vehicle): array
    {
        return [
            'KM' => intval($vehicle['km']),
            'MotorNo' => '',
            'VinNo' => $vehicle['vin'],
            'WarrantyEndDate' => '',
            'WarrantyStartDate' => '',
            'WarrantyStatusTitle' => '',
            'WarrantyStatus' => '',
            'WarrantyDescription' => '',
            'LastDateService' => $vehicle['date'],
            'IsReplacedMotor' => '',
            'LastDateReplaceMotor' => '',
            'IsReplacedGearBox' => '',
            'LastDateReplaceGearbox' => '',
            'IsReplacedBody' => '',
            'LastDateReplaceBody' => '',
            'IsReplacedCarRoom' => '',
            'LastDateReplaceCarRoom' => '',
        ];
    }
}
