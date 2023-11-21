<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActionLogResource;
use App\Repositories\ActionLogRepository;

class ActionLogController extends CrudController
{
    /**
     * @param  ActionLogRepository  $repository
     */
    public function __construct(public readonly  ActionLogRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(ActionLogResource::class);
    }
}
