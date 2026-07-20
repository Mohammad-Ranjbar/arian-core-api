<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlternativeItemResource;
use App\Repositories\AlternativeItemRepository;

class AlternativeItemController extends CrudController
{

    public function __construct(public readonly AlternativeItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AlternativeItemResource::class);
    }
}
