<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\IncidentSource;

class IncidentSourceRepository extends BaseRepository
{
    /**
     * @param  IncidentSource  $model
     */
    public function __construct(IncidentSource $model)
    {
        $this->model = $model;

    }

}