<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionLineResource;
use App\Repositories\ProductionLineRepository;

class ProductionLineController extends CrudController
{
    /**
     * @param  ProductionLineRepository  $repository
     */
    public function __construct(ProductionLineRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(ProductionLineResource::class);
    }
}
