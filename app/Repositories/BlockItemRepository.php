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
            'vin'             => 'required|exists:vehicles,vin',
        ],
        'update' => [
            'block_reason_id' => 'required|exists:block_reasons,id',
            'description'     => 'nullable',
            'vin'             => 'required|exists:vehicles,vin',
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