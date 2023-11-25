<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Repositories\FileRepository;

class FileController extends CrudController
{

    public function __construct(public readonly FileRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(FileResource::class);
    }
}
