<?php

namespace App\Http\Controllers;

use App\Http\Resources\RelatedItemShortageResource;
use App\Repositories\RelatedItemShortageRepository;

class RelatedItemShortageController extends CrudController
{

    public function __construct(public readonly RelatedItemShortageRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(RelatedItemShortageResource::class);
    }
}
