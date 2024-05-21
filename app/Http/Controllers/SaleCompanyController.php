<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleCompanyResource;
use App\Repositories\SaleCompanyRepository;

class SaleCompanyController extends CrudController
{

    public function __construct(public readonly SaleCompanyRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SaleCompanyResource::class);
    }
}
