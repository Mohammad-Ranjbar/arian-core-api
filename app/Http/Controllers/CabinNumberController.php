<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCabinNumberRequest;
use App\Http\Requests\TrackingReportExcelRequest;
use App\Http\Resources\CabinNumberResource;
use App\Jobs\ExportJob;
use App\Models\CabinNumber;
use App\Models\ColorMap;
use App\Models\Job;
use App\Models\Vehicle;
use App\Models\YearMap;
use App\Repositories\CabinNumberRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CabinNumberController extends CrudController
{

    public function __construct(public readonly CabinNumberRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(CabinNumberResource::class);
    }

    public function generate(Vehicle $vehicle, GenerateCabinNumberRequest $request): CabinNumberResource|JsonResponse
    {
        $validatedData = $request->validated();

        if (!$vehicle->carModel->bodyOptions()->where('code', $validatedData['body_option_chr'])->exists()) {
            return $this->responseError('body option chr in invalid');
        }

        if (empty($vehicle->getAttribute('data')['ColorCode'])) {
            return $this->responseError('Color code not exists in vehicle');
        }
        $yearMap = YearMap::query()->where('year', $vehicle->getAttribute('data')['Model_Year'])->first();
        if (empty($yearMap)) {
            return $this->responseError('Can not find year:'.$vehicle->getAttribute('data')['Model_Year']);
        }
        if (empty($yearMap->chr)) {
            return $this->responseError('Update year: '.$vehicle->getAttribute('data')['Model_Year']);
        }

        $colorMap = ColorMap::query()->where('color_code', $vehicle->getAttribute('data')['Color_Code'])->first();
        if (empty($colorMap)) {
            return $this->responseError('Can not find color: '.$vehicle->getAttribute('data')['Color_Code']);
        }
        if (empty($colorMap->code)) {
            return $this->responseError('Update Color: '.$vehicle->getAttribute('data')['Color_Code']);
        }

        if (empty($vehicle->carModel->getAttribute('cabin_serial_chr_1'))){
            return $this->responseError('please update your car model');
        }
        if (empty($vehicle->carModel->getAttribute('cabin_serial_chr_2'))){
            return $this->responseError('please update your car model');
        }
        $counter = CabinNumber::query()
                ->where('serial_1_chr', $vehicle->carModel->getAttribute('cabin_serial_chr_1'))
                ->where('serial_2_chr', $vehicle->carModel->getAttribute('cabin_serial_chr_2'))
                ->where('year_chr', $yearMap->chr)->max('counter');

        if ($counter == 0){
            $r = $vehicle->carModel->getAttribute('cabin_serial_chr_1').$vehicle->carModel->getAttribute('cabin_serial_chr_2').$yearMap->chr;
            $counter = config('cabin_number.'.$r,0);
        }

        $validatedData['car_model_id'] = $vehicle->getAttribute('car_model_id');
        $validatedData['user_id'] = auth()->id();
        $validatedData['serial_1_chr'] = $vehicle->carModel->getAttribute('cabin_serial_chr_1');
        $validatedData['serial_2_chr'] = $vehicle->carModel->getAttribute('cabin_serial_chr_2');


        $validatedData['color_chr'] = $colorMap->getAttribute('code');
        $validatedData['year_chr'] = $yearMap->getAttribute('chr');
        $validatedData['counter'] = $counter+1;

        return CabinNumberResource::make($this->repository->store($validatedData));
    }

    public function show($id)
    {
        if (isset($this->repository)) {
            return new $this->resource($this->repository->show($id));
        }
        return $this->responseError('repository not found',400);
    }

    /**
     * @throws AuthorizationException
     */
    public function reportExcel(Request $request): JsonResponse
    {
        $this->authorize('export-cabin-numbers');
        $validatedData['type'] = 'export-cabin-numbers';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = $request->all();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'export job created',
            'data'    => [
                'uuid'         => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }
}
