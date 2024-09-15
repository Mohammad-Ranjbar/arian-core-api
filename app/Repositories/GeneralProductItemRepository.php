<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\GeneralProductItem;

class GeneralProductItemRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:255',
            'code' => 'required|string|unique:general_product_items,code',
            'carModels' => 'nullable|array|exists:car_models,id',
            'height' => 'required|integer|between:1,2',
            'priority' => 'required|integer',
        ], 'update' => [
            'title' => 'required|string|max:255',
            'code' => 'required|string|unique:general_product_items,code,',
            'carModels' => 'nullable|array|exists:car_models,id',
            'height' => 'required|integer|between:1,2',
            'priority' => 'required|integer',
        ]
    ];

    /**
     * @param  GeneralProductItem  $model
     */
    public function __construct(GeneralProductItem $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['code'] .= request()->route()->parameter('general_product_item');
        }
    }

}
