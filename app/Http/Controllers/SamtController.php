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

        $vehicleInfo = DWVehicleServiceHistoryTree::query()->latest('date')->where('vin', $validatedData['VinNO']);
        if ($vehicleInfo->count() === 0) {
            return response()->json([
                'resultCode' => 404,
                'resultMessage' => 'خودرو مورد نظر یافت نشد',
                'Obj' => []
            ]);
        }

      $vehicle = $vehicleInfo->first();
        $data = [
            'KM' => intval($vehicle->mileage),
            'MotorNo' => $vehicle->getAttribute('engine_no'),
            'VinNo' => $vehicle->getAttribute('vin'),
            'WarrantyEndDate' => $vehicle->getAttribute('beginning_of_contract'),
            'WarrantyStartDate' => $vehicle->getAttribute('expiration_of_contract'),
            'WarrantyStatusTitle' => $vehicle->getAttribute('reason_description'),
            'WarrantyStatus' => intval($vehicle->getAttribute('cancel_warranty')),
            'WarrantyDescription' => $vehicle->getAttribute('reason_description'),
            'LastDateService' => $vehicle->date,
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


        foreach ($lines as $line) {
            if (in_array( $line->document_no,self::GEAR_BOX_CODES)) {
                $data['IsReplacedGearBox'] = true;
                $data['LastDateReplaceGearbox'] = $line->date;
            }
            if (in_array( $line->document_no,self::MOTOR_CODES)) {
                $data['IsReplacedMotor'] = true;
                $data['LastDateReplaceMotor'] = $line->date;
            }
            if (in_array( $line->document_no,self::CASSIS_BODY_CODES)) {
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
