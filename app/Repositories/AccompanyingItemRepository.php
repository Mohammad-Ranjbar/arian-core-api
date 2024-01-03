<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\AccompanyingItem;

class AccompanyingItemRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title'        => 'required|string|unique:accompanying_items',
        ], 'update' => [
            'title'        => 'required|string|unique:accompanying_items',
        ]
    ];

    /**
     * @param  AccompanyingItem  $model
     */
    public function __construct(AccompanyingItem $model)
    {
        $this->model = $model;
    }

}