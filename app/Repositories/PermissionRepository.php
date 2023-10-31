<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Permission;

class PermissionRepository extends BaseRepository
{
    /**
     * @param  Permission  $model
     */
    public function __construct(Permission $model)
    {
        $this->model = $model;
    }

}