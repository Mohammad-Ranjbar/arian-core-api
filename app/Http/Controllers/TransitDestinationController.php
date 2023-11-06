<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitDestinationResource;
use App\Repositories\TransitDestinationRepository;

class TransitDestinationController extends CrudController
{
    /**
     * @param  TransitDestinationRepository  $repository
     */
    public function __construct(TransitDestinationRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(TransitDestinationResource::class);
    }
}
