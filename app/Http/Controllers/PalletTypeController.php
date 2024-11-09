<?php

namespace App\Http\Controllers;

use App\Http\Resources\PalletTypeResource;
use App\Repositories\PalletTypeRepository;

class PalletTypeController extends CrudController
{

    public function __construct(public readonly PalletTypeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(PalletTypeResource::class);
    }
}
