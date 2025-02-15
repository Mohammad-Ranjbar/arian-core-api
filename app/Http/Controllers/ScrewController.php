<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleTorqueRequest;
use App\Http\Resources\ScrewResource;
use App\Http\Resources\VehicleTorqueTestResource;
use App\Models\Vehicle;
use App\Models\VehicleTorqueTest;
use App\Repositories\ScrewRepository;
use Illuminate\Http\JsonResponse;

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


}
