<?php

namespace App\Http\Controllers;

use App\Http\Resources\SpecificShortageItemResource;
use App\Repositories\SpecificShortageItemRepository;

class SpecificShortageItemController extends CrudController
{
    public function __construct(public readonly SpecificShortageItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SpecificShortageItemResource::class);
    }
}
