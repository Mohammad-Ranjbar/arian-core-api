<?php

namespace App\Http\Controllers;

use App\Http\Resources\SmsServiceResource;
use App\Repositories\SmsServiceRepository;

class SmsServiceController extends CrudController
{
    public function __construct(public readonly SmsServiceRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SmsServiceResource::class);
    }
}
