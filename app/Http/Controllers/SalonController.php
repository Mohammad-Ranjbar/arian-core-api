<?php

namespace App\Http\Controllers;

use App\Http\Resources\SalonResource;
use App\Repositories\SalonRepository;

class SalonController extends CrudController
{
    /**
     * @param  SalonRepository  $repository
     */
    public function __construct( SalonRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(SalonResource::class);
    }
}
