<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\BlockReason;

class BlockReasonRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string',
        ],
        'update' => [
            'title' => 'required|string',
        ]
    ];

    /**
     * @param  BlockReason  $model
     */
    public function __construct(BlockReason $model)
    {
        $this->model = $model;
    }

}