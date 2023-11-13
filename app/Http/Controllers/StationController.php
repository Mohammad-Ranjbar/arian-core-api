<?php

namespace App\Http\Controllers;

use App\Http\Resources\StationResource;
use App\Repositories\StationRepository;

class StationController extends CrudController
{
    public function __construct(public readonly StationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(StationResource::class);
    }
}
