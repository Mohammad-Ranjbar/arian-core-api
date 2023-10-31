<?php

namespace App\Http\Controllers;

use App\Http\Resources\MakeCodeResource;
use App\Repositories\MakeCodeRepository;

class MakeCodeController extends CrudController
{
    /**
     * @param  MakeCodeRepository  $repository
     */
    public function __construct(MakeCodeRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(MakeCodeResource::class);
    }
}
