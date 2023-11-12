<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ErpProductItem;

class ErpProductItemRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [

        ],
        'update' => [

        ]
    ];

    /**
     * @param  ErpProductItem  $model
     */
    public function __construct(ErpProductItem $model)
    {
        $this->model = $model;
    }

}