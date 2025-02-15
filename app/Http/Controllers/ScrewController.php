<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScrewResource;
use App\Repositories\ScrewRepository;

class ScrewController extends CrudController
{

    public function __construct(public readonly ScrewRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ScrewResource::class);
    }
}
