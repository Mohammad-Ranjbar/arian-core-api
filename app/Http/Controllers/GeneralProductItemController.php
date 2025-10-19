<?php

namespace App\Http\Controllers;

use App\Http\Resources\GeneralProductItemResource;
use App\Models\Vehicle;
use App\Repositories\GeneralProductItemRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class GeneralProductItemController extends CrudController
{
    public function __construct(public readonly GeneralProductItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(GeneralProductItemResource::class);
    }

    public function carModelItems(string $carModelId): JsonResponse
    {
        $items = $this->repository->model->newQuery()->with('carModels')->whereHas('carModels',
            function ($query) use ($carModelId) {
                $query->where('car_model_id', $carModelId);
            })->get();

        return new JsonResponse(
            data: GeneralProductItemResource::collection($items),
        );
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws AuthorizationException
     * @throws ContainerExceptionInterface
     */
    public function vehicleItems(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('general-product-items.vehicle');
        $carModelId = $vehicle->getAttribute('car_model_id');

        $items = $this->repository->model->newQuery()
            ->when(request()->get('type'), function ($q) {
                $q->where('type', request()->get('type'));
            })
            ->whereHas('carModels', function ($query) use ($carModelId) {
                $query->where('car_model_id', $carModelId);
            })->orderBy('priority')->get();

        return new JsonResponse(
            data: GeneralProductItemResource::collection($items),
        );
    }
}
