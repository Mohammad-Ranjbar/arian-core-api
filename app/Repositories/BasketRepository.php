<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Basket;

class BasketRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'code' => 'required|string',
        ], 'update' => [
            'batch' => 'nullable|string'
        ]
    ];

    /**
     * @param Basket $model
     */
    public function __construct(Basket $model)
    {
        $this->model = $model;
    }

}
