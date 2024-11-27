<?php

namespace App\Http\Controllers;

use App\Http\Resources\YearMapResource;
use App\Repositories\YearMapRepository;

class YearMapController extends CrudController
{

    public function __construct(public readonly YearMapRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(YearMapResource::class);
    }
}
