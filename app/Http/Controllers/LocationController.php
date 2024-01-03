<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationResource;
use App\Repositories\LocationRepository;

class LocationController extends CrudController
{

    public function __construct(public readonly LocationRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(LocationResource::class);
    }

    public function afterSave($record, $data)
    {
        $record->attributes()->delete();

        foreach ($data['attributes'] as $attribute => $value) {
            $record->attributes()->create([
                'attribute' => $attribute,
                'value'     => $value,
            ]);
        }
        return $record;
    }

}
