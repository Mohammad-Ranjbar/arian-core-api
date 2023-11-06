<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\BlockItem;
use Illuminate\Support\Facades\Auth;

class BlockItemRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */

    protected array $rules = [
        'create' => [
            'block_reason_id' => 'required|exists:block_reasons,id',
            'description'     => 'nullable',
        ],
        'update' => [
            'block_reason_id' => 'required|exists:block_reasons,id',
            'description'     => 'nullable',
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