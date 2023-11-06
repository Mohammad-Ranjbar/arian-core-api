<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitCompanyResource;
use App\Repositories\TransitCompanyRepository;

class TransitCompanyController extends CrudController
{
    /**
     * @param  TransitCompanyRepository  $repository
     */
    public function __construct(TransitCompanyRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(TransitCompanyResource::class);
    }
}
