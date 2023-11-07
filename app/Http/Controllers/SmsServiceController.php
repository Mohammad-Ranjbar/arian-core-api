<?php

namespace App\Http\Controllers;

use App\Http\Resources\SmsServiceResource;
use App\Repositories\SmsServiceRepository;

class SmsServiceController extends CrudController
{
    /**
     * @param  SmsServiceRepository  $repository
     */
    public function __construct(SmsServiceRepository $repository)
    {
        $this->repository = $repository;
        $this->applyAuthorizeResource();
        $this->setResource(SmsServiceResource::class);
    }
}
