<?php

namespace App\Http\Controllers;

use App\Http\Resources\InspectionResource;
use App\Repositories\InspectionRepository;

class InspectionController extends CrudController
{

    public function __construct(public readonly InspectionRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(InspectionResource::class);
    }
}
