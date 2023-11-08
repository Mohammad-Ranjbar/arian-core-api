<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductionLineActivityLogs;

class ProductionLineActivityLogsRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [

        ], 'update' => [

        ]
    ];

    /**
     * @param  ProductionLineActivityLogs  $model
     */
    public function __construct(ProductionLineActivityLogs $model)
    {
        $this->model = $model;
    }

}