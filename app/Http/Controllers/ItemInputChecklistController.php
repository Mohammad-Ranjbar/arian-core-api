<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemInputChecklistResource;
use App\Repositories\ItemInputChecklistRepository;
use Illuminate\Http\Request;

class ItemInputChecklistController extends CrudController
{
    public function __construct(public readonly ItemInputChecklistRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ItemInputChecklistResource::class);
    }
}
