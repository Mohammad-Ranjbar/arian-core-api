<?php

namespace App\Http\Controllers;

use App\Http\Resources\GeneralProductItemResource;
use App\Models\Vehicle;
use App\Repositories\GeneralProductItemRepository;
use Illuminate\Http\JsonResponse;

class GeneralProductItemController extends CrudController
{

    public function __construct(public readonly GeneralProductItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(GeneralProductItemResource::class);
    }

    public function carModelItems(string $carModelId): JsonResponse
    {
        $items = $this->repository->model->newQuery()->with('carModels')->whereHas('carModels', function ($query) use ($carModelId) {
            $query->where('car_model_id', $carModelId);
        })->get();

        return new JsonResponse(
            data: GeneralProductItemResource::collection($items),
        );
    }

    public function vehicleItems(Vehicle $vehicle): JsonResponse
    {
        $carModelId = $vehicle->getAttribute('car_model_id');

        $items = $this->repository->model->newQuery()->whereHas('carModels', function ($query) use ($carModelId) {
            $query->where('car_model_id', $carModelId);
        })->get();

        return new JsonResponse(
            data: GeneralProductItemResource::collection($items),
        );
    }
}
