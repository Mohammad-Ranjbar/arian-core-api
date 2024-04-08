<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Repositories\RoleRepository;
use Illuminate\Support\Facades\Artisan;

class RoleController extends CrudController
{
    public function __construct(public readonly RoleRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(RoleResource::class);
    }

    public function afterSave($record, $data)
    {
        foreach ($data as $key => $input) {
            if (method_exists($record, $key) and is_array($input)) {
                $record->{$key}()->sync($input);
            }
        }

        Artisan::call('cache:clear');
        return $record;
    }
}
