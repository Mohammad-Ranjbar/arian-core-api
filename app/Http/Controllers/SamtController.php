<?php

namespace App\Http\Controllers;

use App\Http\Requests\SamtRequest;
use App\Models\DWVehicleServiceHistoryTree;

class SamtController extends Controller
{

    public function __invoke(SamtRequest $request)
    {
        $validatedData = $request->validated();
        dd(DwVehicleServiceHistoryTree::query()->latest('create_date')->first()->toArray());
//        $vehicleInfo = DWVehicleServiceHistoryTree::query()->where('vin', $validatedData['VinNO'])->first();
//
//        dd($vehicleInfo);
        $result = [
            'resultCode' => 1,
            'resultMessage' => '',
            'Obj' => ''
        ];
    }


    private function obj()
    {
        $data = [
            'KM' => '',
            'MotorNo' => '',
            'VinNo' => '',
            'WarrantyEndDate' => '',
            'WarrantyStartDate' => '',
            'WarrantyStatusTitle' => '',
            'WarrantyStatus' => '',
            'WarrantyDescription' => '',
            'LastDateService' => '',
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
