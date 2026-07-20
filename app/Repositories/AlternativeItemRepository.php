<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\AlternativeItem;

class AlternativeItemRepository extends BaseRepository
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
     * @param  AlternativeItem  $model
     */
    public function __construct(AlternativeItem $model)
    {
        $this->model = $model;
    }

}