<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemTechnicalChecklistResource;
use App\Repositories\ItemTechnicalChecklistRepository;

class ItemTechnicalChecklistController extends CrudController
{
    public function __construct(public readonly ItemTechnicalChecklistRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ItemTechnicalChecklistResource::class);
    }
}
