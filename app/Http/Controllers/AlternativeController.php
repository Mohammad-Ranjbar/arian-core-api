<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlternativeResource;
use App\Repositories\AlternativeRepository;

class AlternativeController extends CrudController
{

    public function __construct(public readonly AlternativeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AlternativeResource::class);
    }
}
