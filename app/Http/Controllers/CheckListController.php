<?php

namespace App\Http\Controllers;

use App\Http\Resources\CheckListResource;
use App\Repositories\CheckListRepository;

class CheckListController extends CrudController
{

    public function __construct(public readonly CheckListRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(CheckListResource::class);
    }
}
