<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleInvoicesResource;
use App\Repositories\SaleInvoicesRepository;

class SaleInvoicesController extends CrudController
{

    public function __construct(public readonly SaleInvoicesRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SaleInvoicesResource::class);
    }
}
