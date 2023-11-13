<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionLineResource;
use App\Repositories\ProductionLineRepository;

class ProductionLineController extends CrudController
{
    public function __construct(public readonly ProductionLineRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionLineResource::class);
    }
}
