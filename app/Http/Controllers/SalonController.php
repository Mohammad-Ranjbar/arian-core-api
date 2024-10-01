<?php

namespace App\Http\Controllers;

use App\Http\Resources\SalonResource;
use App\Repositories\SalonRepository;

class SalonController extends CrudController
{
    /**
     * @param  SalonRepository  $repository
     */
    public function __construct(public readonly SalonRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(SalonResource::class);
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
