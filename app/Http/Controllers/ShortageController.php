<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShortageItemResource;
use App\Repositories\ShortageItemRepository;

class ShortageController extends CrudController
{
    protected bool $appendUserId = true;
    public function __construct(public readonly ShortageItemRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ShortageItemResource::class);
    }
}
