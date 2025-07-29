<?php

namespace App\Http\Controllers;

use App\Http\Resources\PdiCheckListResource;
use App\Http\Resources\ProductionControlResource;
use App\Repositories\PdiCheckListRepository;
use App\Repositories\ProductionControlRepository;

class PdiCheckListController extends CrudController
{
    public function __construct(public readonly PdiCheckListRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(PdiCheckListResource::class);
    }
}
