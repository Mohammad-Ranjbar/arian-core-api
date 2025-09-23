<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\PDI;

class PDIRepository extends BaseRepository
{
    /**
     * @param PDI $model
     */
    public function __construct(PDI $model)
    {
        $this->model = $model;
    }

}
