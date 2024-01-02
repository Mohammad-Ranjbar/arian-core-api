<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccompanyingItemResource;
use App\Repositories\AccompanyingItemRepository;

class AccompanyingItemController extends CrudController
{

    public function __construct(public readonly AccompanyingItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(AccompanyingItemResource::class);
    }
}
