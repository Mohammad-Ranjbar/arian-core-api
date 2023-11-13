<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionLineActivityResource;
use App\Repositories\ProductionLineActivityRepository;

class ProductionLineActivityController extends CrudController
{
    public function __construct(public readonly ProductionLineActivityRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionLineActivityResource::class);
    }

}
