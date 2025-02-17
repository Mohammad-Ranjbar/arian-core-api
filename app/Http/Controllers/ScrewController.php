<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleTorqueRequest;
use App\Http\Resources\ScrewResource;
use App\Http\Resources\VehicleTorqueTestResource;
use App\Jobs\ExportJob;
use App\Models\Job;
use App\Models\Vehicle;
use App\Models\VehicleTorqueTest;
use App\Repositories\ScrewRepository;
use Illuminate\Auth\Access\AuthorizationException;
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


    public function vehicleTorqueTest(StoreVehicleTorqueRequest $request): VehicleTorqueTestResource
    {
        $validatedData = $request->validated();

        $data = VehicleTorqueTest::query()->create($validatedData);

        return new VehicleTorqueTestResource($data);
    }

    /**
     * @throws AuthorizationException
     */
    public function reportExcel(): JsonResponse
    {
        $this->authorize('export-vehicle-torque-test');
        $validatedData['type'] = 'export-vehicle-torque-test';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = request()->all();
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
