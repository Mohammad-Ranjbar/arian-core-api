<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductionLine;

class ProductionLineRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title'    => 'required|string|unique:production_lines,title',
            'salon_id' => 'required|exists:salons,id',
        ],
        'update' => [
            'title'    => 'required|string|unique:production_lines,title,',
            'salon_id' => 'required|exists:salons,id',
        ]
    ];

    /**
     * @param  ProductionLine  $model
     */
    public function __construct(ProductionLine $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('production_line');
        }
    }

}