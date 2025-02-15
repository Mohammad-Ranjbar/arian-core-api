<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScrewResource;
use App\Models\Vehicle;
use App\Repositories\ScrewRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ScrewController extends CrudController
{

    public function __construct(public readonly ScrewRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ScrewResource::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function listScrewsForVin(Vehicle $vehicle): AnonymousResourceCollection
    {
        $carModelId = $vehicle->carModel->id;

        $data = $this->repository->model->newQuery()->whereHas('carModels', function ($q) use ($carModelId) {
            $q->where('id', $carModelId);
        })->get();

        return ScrewResource::collection($data);
    }


}
