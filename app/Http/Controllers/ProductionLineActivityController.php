<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionLineActivityResource;
use App\Repositories\ProductionLineActivityRepository;

class ProductionLineActivityController extends CrudController
{
    /**
     * @param  ProductionLineActivityRepository  $repository
     */
    public function __construct(ProductionLineActivityRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(ProductionLineActivityResource::class);
    }

}
