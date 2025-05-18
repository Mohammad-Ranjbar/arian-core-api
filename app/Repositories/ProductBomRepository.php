<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductBom;

class ProductBomRepository extends BaseRepository
{
    /**
     * @var array|string[]
     */
    public array $searchTerms = [
        'title'
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ],
        'update' => [
            'title' => 'required',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ]
    ];

    /**
     * @param ProductBom $model
     */
    public function __construct(ProductBom $model)
    {
        $this->model = $model;
    }

    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
            ];
        })->all();
    }

}
