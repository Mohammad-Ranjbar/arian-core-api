<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\BlockItem;

class BlockItemRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */

    protected array $rules = [
        'create'    => [
            'block_reason_id' => 'required|exists:block_reasons,id',
            'description'     => 'nullable',
            'blocked_by'      => 'nullable',
            'vin'             => 'nullable',
        ], 'update' => [
            'block_reason_id' => 'required|exists:block_reasons,id',
            'description'     => 'nullable',
            'blocked_by'      => 'nullable',
            'vin'             => 'nullable',
        ]
    ];

    /**
     * @param  BlockItem  $model
     */
    public function __construct(BlockItem $model)
    {
        $this->model = $model;
    }

}