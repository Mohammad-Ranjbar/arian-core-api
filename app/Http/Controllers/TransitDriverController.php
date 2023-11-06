<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitDriverResource;
use App\Repositories\TransitDriverRepository;

class TransitDriverController extends CrudController
{
    /**
     * @param  TransitDriverRepository  $repository
     */
    public function __construct(TransitDriverRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(TransitDriverResource::class);
    }
}
