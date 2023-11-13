<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Inventory;

class InventoryRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title'        => 'required|string',
            'make_code_id' => 'required|exists:make_codes,id',
            'code'         => 'required|string'
        ], 'update' => [
            'title'        => 'required|string',
            'make_code_id' => 'required|exists:make_codes,id',
            'code'         => 'required|string'
        ]
    ];

    /**
     * @param  Inventory  $model
     */
    public function __construct(Inventory $model)
    {
        $this->model = $model;
    }

}