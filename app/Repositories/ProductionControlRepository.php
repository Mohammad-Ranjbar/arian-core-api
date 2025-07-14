<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductionControl;

class ProductionControlRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string|unique:production_controls,title',
            'makeCodes' => 'required|array',
            'makeCodes.*' => 'required|exists:make_codes,id',
        ], 'update' => [
            'title' => 'required|string|unique:production_controls,title,',
            'makeCodes' => 'required|array',
            'makeCodes.*' => 'required|exists:make_codes,id',
        ]
    ];


    public function __construct(ProductionControl $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('production_control');
        }
    }

}
