<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransitDestinationResource;
use App\Repositories\TransitDestinationRepository;

class TransitDestinationController extends CrudController
{
    public function __construct(public readonly TransitDestinationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TransitDestinationResource::class);
    }
}
