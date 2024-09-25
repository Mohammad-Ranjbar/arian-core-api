<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ActionLog;

class ShortageItemRepository extends BaseRepository
{


    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'erp_product_item_id' => 'required|exists:erp_product_items,id',
        ], 'update' => [
            'status' => 'required|in:done'
        ]
    ];

    /**
     * @param ActionLog $model
     */
    public function __construct(ActionLog $model)
    {
        $this->model = $model;
    }



}
