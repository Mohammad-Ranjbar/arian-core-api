<?php

namespace App\Http\Controllers;

use App\Http\Requests\SamtRequest;

class SamtController extends Controller
{

    public function __invoke(SamtRequest $request)
    {

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
          'MotorNo' => ''  ,
          'VinNo' => ''  ,
          'WarrantyEndDate' => ''  ,
          'WarrantyStartDate' => ''  ,
          'WarrantyStatusTitle' => ''  ,
          'WarrantyStatus' => ''  ,
          'WarrantyDescription' => ''  ,
          'LastDateService' => ''  ,
          'IsReplacedMotor' => ''  ,
          'LastDateReplaceMotor' => ''  ,
          'IsReplacedGearBox' => ''  ,
          'LastDateReplaceGearbox' => ''  ,
          'IsReplacedBody' => ''  ,
          'LastDateReplaceBody' => ''  ,
          'IsReplacedCarRoom' => ''  ,
          'LastDateReplaceCarRoom' => ''  ,
        ];
    }
}
