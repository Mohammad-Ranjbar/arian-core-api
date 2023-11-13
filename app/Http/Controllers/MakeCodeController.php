<?php

namespace App\Http\Controllers;

use App\Http\Resources\MakeCodeResource;
use App\Repositories\MakeCodeRepository;

class MakeCodeController extends CrudController
{
    public function __construct(public readonly MakeCodeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(MakeCodeResource::class);
    }
}
