<?php

namespace App\Http\Controllers;

use App\Http\Resources\BodyOptionResource;
use App\Repositories\BodyOptionRepository;

class BodyOptionController extends CrudController
{

    public function __construct(public readonly BodyOptionRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(BodyOptionResource::class);
    }
}
