<?php

namespace App\Repositories;

use App\Filters\UserMakeCodeFilter;
use App\libs\BaseRepository;
use App\Models\CarModel;

class CarModelRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'reference' => 'required|unique:car_models,reference',
            'title' => 'required|string',
            'cabin_serial_chr_1' => 'nullable|string',
            'cabin_serial_chr_2' => 'nullable|string',
            'productionLines' => 'required|array',
            'productionLines.*' => 'required|exists:production_lines,id',
            'bodyOptions' => 'nullable|array',
            'bodyOptions.*' => 'nullable|exists:body_options,id',
        ],
        'update' => [
            'reference' => 'required|unique:car_models,reference,',
            'title' => 'required|string',
            'cabin_serial_chr_1' => 'nullable|string',
            'cabin_serial_chr_2' => 'nullable|string',
            'productionLines' => 'required|array',
            'productionLines.*' => 'required|exists:production_lines,id',
            'bodyOptions' => 'nullable|array',
            'bodyOptions.*' => 'nullable|exists:body_options,id',
        ]
    ];

    /**
     * @param CarModel $model
     */
    public function __construct(CarModel $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['reference'] .= request()->route()->parameter('car_model');
        }
        $this->filters[] = UserMakeCodeFilter::class;
    }

    public function getOptions(): array
    {
        return $this->get('id', 'asc', request()->get('limit'))->map(function ($item) {
            return ['id' => $item->id, 'title' => $item->title, 'make_code_id' => $item->make_code_id];
        })->all();
    }

}
