<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkGroupCenterResource;
use App\Repositories\WorkGroupCenterRepository;

class WorkGroupCenterController extends CrudController
{
    public function __construct(public readonly WorkGroupCenterRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(WorkGroupCenterResource::class);
    }
    public function afterSave($record,$data)
    {
        $record->sendErp()->delete();
        $record->sendErp()->create([
            'on_enter' => $data['on_enter'],
            'on_exit'  => $data['on_exit'],
        ]);

        return $record;
    }
}
