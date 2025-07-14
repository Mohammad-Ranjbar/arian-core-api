<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionControlResource;
use App\Repositories\ProductionControlRepository;

class ProductionControlController extends CrudController
{
    public function __construct(public readonly ProductionControlRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionControlResource::class);
    }
}
