<?php

namespace App\Http\Controllers;

use App\Http\Requests\SamtRequest;
use App\Models\DWVehicleServiceHistoryTree;
use Illuminate\Http\JsonResponse;

class SamtController extends Controller
{

    const array GEAR_BOX_CODES = [
        '1700100F0709A',
        'SX5G-1700100',
        'SX5G-1700100B',
        'SX5G-1700100B-R',
        'SX5G-1700100B-RP',
        'T5HEV-2151010',
    ];
    const array MOTOR_CODES = [
        '4A95UAL04',
        '4A95-U-AL04',
        'SFG15TA-F0711',
        'SX5G-1000-0001C',
        'SX5G-ABC-0001C',
    ];
    const array CASSIS_BODY_CODES = [
        'SX5G-5000050BS8',
        'SX5G-5000050BS8-NCP',
    ];

    public function __invoke(SamtRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $vehicleInfo = DWVehicleServiceHistoryTree::query()->where('vin', $validatedData['VinNO']);
        if ($vehicleInfo->count() === 0) {
            return response()->json([
                'resultCode' => 404,
                'resultMessage' => 'خودرو مورد نظر یافت نشد',
                'Obj' => []
            ]);
        }

        $data = [
            'KM' => intval($vehicleInfo->latest('date')->first()->km),
            'MotorNo' => '',//not found
            'VinNo' => $vehicleInfo->first()->getAttribute('vin'),
            'WarrantyEndDate' => '',//not found
            'WarrantyStartDate' => '',//not found
            'WarrantyStatusTitle' => '',//not found
            'WarrantyStatus' => '',
            'WarrantyDescription' => '',//not found
            'LastDateService' => $vehicleInfo->latest('date')->first()->date,
            'IsReplacedMotor' => false,
            'LastDateReplaceMotor' => '',
            'IsReplacedGearBox' => false,
            'LastDateReplaceGearbox' => '',
            'IsReplacedBody' => false,
            'LastDateReplaceBody' => '',
            'IsReplacedCarRoom' => false,
            'LastDateReplaceCarRoom' => '',
        ];

        $lines = $vehicleInfo->get();
        if ($vehicleInfo->latest('date')->first()->includes_warranty == 1){
            $data['WarrantyStatus'] = 1;
        }elseif ($vehicleInfo->latest('date')->first()->includes_warranty !== 1){
            //TODO need data from warehouse
            //implement expired and canceled status and description for canceled warranty
            $data['WarrantyStatus'] = 0;
            $data['WarrantyDescription'] = '';//for canceled warranty
        }

        foreach ($lines as $line) {
            if (in_array(self::GEAR_BOX_CODES,$line->no)){
                $data['IsReplacedGearBox'] = true;
                $data['LastDateReplaceGearbox'] = $line->date;
            }
            if (in_array(self::MOTOR_CODES,$line->no)){
                $data['IsReplacedMotor'] = true;
                $data['LastDateReplaceMotor'] = $line->date;
            }
            if (in_array(self::CASSIS_BODY_CODES,$line->no)){
                $data['IsReplacedBody'] = true;
                $data['LastDateReplaceBody'] = $line->date;
                $data['IsReplacedCarRoom'] = true;
                $data['LastDateReplaceCarRoom'] = $line->date;
            }
        }



        return response()->json([
            'resultCode' => 200,
            'resultMessage' => '',
            'Obj' => $data
        ]);
    }



}
