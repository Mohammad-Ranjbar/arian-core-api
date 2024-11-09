<?php

namespace App\Http\Controllers;

use App\Http\Resources\PalletResource;
use App\Repositories\PalletRepository;

class PalletController extends CrudController
{

    public function __construct(public readonly PalletRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(PalletResource::class);
    }
}
