<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitCompanyResource;
use App\Repositories\TransitCompanyRepository;

class TransitCompanyController extends CrudController
{
    public function __construct(public readonly TransitCompanyRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TransitCompanyResource::class);
    }
}
