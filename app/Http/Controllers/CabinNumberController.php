<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCabinNumberRequest;
use App\Http\Resources\CabinNumberResource;
use App\Models\ColorMap;
use App\Models\Vehicle;
use App\Repositories\CabinNumberRepository;
use Illuminate\Http\Request;

class CabinNumberController extends CrudController
{

    public function __construct(public readonly CabinNumberRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(CabinNumberResource::class);
    }

    public function generate(Vehicle $vehicle, GenerateCabinNumberRequest $request)
    {
        $validatedData = $request->validated();


       if(! $vehicle->carModel->bodyOptions()->where('code',$validatedData['body_option_chr'])->exists()){
           return $this->responseError('body option chr in invalid');
       }
        if (empty($vehicle->getAttribute('data')['Color_Code'])){
            return $this->responseError('Color code not exists in vehicle');
        }
        $colorMap = ColorMap::query()->where('color_code',$vehicle->getAttribute('data')['Color_Code'])->first();
        if (empty($colorMap)){
            return $this->responseError('Color code not exists in color map');
        }
       $validatedData['car_model_id'] = $vehicle->getAttribute('car_model_id');
       $validatedData['user_id'] = auth()->id();
       $validatedData['serial_1_chr'] = '';
       $validatedData['serial_2_chr'] = '';

        $validatedData['color_chr'] = $colorMap->getAttribute('code');

        $validatedData['color_code'] = $colorMap->getAttribute('code');
        $validatedData['year_chr'] = $vehicle->carModel->yearMap->getAttribute('chr');
        $validatedData['counter'] = 1;



    }

}
