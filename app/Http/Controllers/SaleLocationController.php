<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleLocationResource;
use App\Repositories\SaleLocationRepository;

class SaleLocationController extends CrudController
{

    public function __construct(public readonly SaleLocationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SaleLocationResource::class);
    }
}
