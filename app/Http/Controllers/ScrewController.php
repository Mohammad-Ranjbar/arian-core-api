<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportExcelToqueTestRequest;
use App\Http\Requests\StoreVehicleTorqueRequest;
use App\Http\Resources\ScrewResource;
use App\Http\Resources\VehicleTorqueTestResource;
use App\Jobs\ExportJob;
use App\Models\Job;
use App\Models\Screw;
use App\Models\TorqueCoefficient;
use App\Models\Vehicle;
use App\Models\VehicleTorqueTest;
use App\Repositories\ScrewRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class ScrewController extends CrudController
{

    public function __construct(public readonly ScrewRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ScrewResource::class);
    }


    public function listScrewsForVin(Vehicle $vehicle): JsonResponse
    {
        $carModelId = $vehicle->carModel->id;

        $data = $this->repository->model->newQuery()->whereHas('carModels', function ($q) use ($carModelId) {
            $q->where('id', $carModelId);
        })->select('id', 'title')->get();

        return response()->json($data);
    }


    public function vehicleTorqueTest(StoreVehicleTorqueRequest $request): VehicleTorqueTestResource|JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $const = TorqueCoefficient::query()->where('is_active', 1)->first();

            if (empty($const)) {
                return $this->responseError('cant find any active torque coefficent');
            }
            $validatedData['torque_coefficient_id'] = $const->getAttribute('id');

            $screw = Screw::query()->find($validatedData['screw_id']);
            $range = [
                $screw->getAttribute('min') * $const->getAttribute('low_3'),
                $screw->getAttribute('min') * $const->getAttribute('low_2'),
                $screw->getAttribute('min') * $const->getAttribute('low_1'),
                $screw->getAttribute('min'),
                $screw->getAttribute('max'),
                $screw->getAttribute('max') * $const->getAttribute('high_1'),
                $screw->getAttribute('max') * $const->getAttribute('high_2'),
                $screw->getAttribute('max') * $const->getAttribute('high_3'),
                $screw->getAttribute('max') * $const->getAttribute('high_4'),
            ];

            $colors = [
                'red' => '#ff0000',
                'orange' => '#ff6600',
                'yellow' => '#ffff00',
                'green' => '#00ff00',
            ];
            if ($validatedData['value'] < $range[0]) {
                $validatedData['range_color'] = 'l4';
                $validatedData['range_code'] = $colors['red'];
            }


            if ($validatedData['value'] >= $range[0] && $validatedData['value'] < $range[1]) {
                $validatedData['range_color'] = 'l3';
                $validatedData['range_code'] = $colors['red'];
            }

            if ($validatedData['value'] >= $range[1] && $validatedData['value'] < $range[2]) {
                $validatedData['range_color'] = 'l2';
                $validatedData['range_code'] = $colors['orange'];
            }

            if ($validatedData['value'] >= $range[2] && $validatedData['value'] < $range[3]) {
                $validatedData['range_color'] = 'l1';
                $validatedData['range_code'] = $colors['yellow'];
            }

            if ($validatedData['value'] > $range[3] && $validatedData['value'] <= $range[4]) {
                $validatedData['range_color'] = 'n';
                $validatedData['range_code'] = $colors['green'];
            }

            if ($validatedData['value'] > $range[4] && $validatedData['value'] <= $range[5]) {
                $validatedData['range_color'] = 'h1';
                $validatedData['range_code'] = $colors['green'];
            }

            if ($validatedData['value'] > $range[5] && $validatedData['value'] <= $range[6]) {
                $validatedData['range_color'] = 'h2';
                $validatedData['range_code'] = $colors['yellow'];
            }
            if ($validatedData['value'] > $range[6] && $validatedData['value'] <= $range[7]) {
                $validatedData['range_color'] = 'h3';
                $validatedData['range_code'] = $colors['orange'];
            }
            if ($validatedData['value'] > $range[7] && $validatedData['value'] <= $range[8]) {
                $validatedData['range_color'] = 'h4';
                $validatedData['range_code'] = $colors['red'];
            }
            if ($validatedData['value'] > $range[8]) {
                $validatedData['range_color'] = 'h5';
                $validatedData['range_code'] = $colors['red'];
            }


            $data = VehicleTorqueTest::query()->create($validatedData);

            return new VehicleTorqueTestResource($data);
        } catch (Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 400);
        }

    }


    public function reportExcel(ReportExcelToqueTestRequest $request): JsonResponse
    {
        $validatedData['type'] = 'export-vehicle-torque-test';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = $request->validated();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'export job created',
            'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

    public function torqueTests(Vehicle $vehicle): AnonymousResourceCollection
    {
        $data = VehicleTorqueTest::query()->where('vin', $vehicle->vin)->latest()->get();

        return VehicleTorqueTestResource::collection($data);
    }

}
