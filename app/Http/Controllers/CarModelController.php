<?php

namespace App\Http\Controllers;

use App\Http\Resources\CarModelResource;
use App\Repositories\CarModelRepository;

class CarModelController extends CrudController
{
    /**
     * @param  CarModelRepository  $repository
     */
    public function __construct(CarModelRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(CarModelResource::class);
    }
}
