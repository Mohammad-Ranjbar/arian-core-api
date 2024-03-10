<?php

namespace App\Http\Controllers;

use App\Http\Resources\BarcodeResource;
use App\Repositories\BarcodeRepository;


class BarcodeController extends CrudController
{
    /**
     * @var bool
     */
    protected bool $appendUserId = true;
    public function __construct(public readonly BarcodeRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(BarcodeResource::class);
    }

    public function afterSave($record, $data)
    {
        $record->updateErpProductItemId();
        return $record;
    }

}
