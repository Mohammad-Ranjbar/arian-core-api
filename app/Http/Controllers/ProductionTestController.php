<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductionTestResource;
use App\Repositories\ProductionTestRepository;

class ProductionTestController extends CrudController
{

    public function __construct(public readonly ProductionTestRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ProductionTestResource::class);
    }
}
