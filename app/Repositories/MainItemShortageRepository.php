<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\MainItemShortage;

class MainItemShortageRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'location_code'       => 'required|string|max:200',
            'car_model_id'        => 'required|exists:car_models,id',
            'car_model_reference' => 'required|string|max:200',
            'item_no'             => 'required|string|max:200',
            'description'         => 'required|string',
            'data'         => 'required',
        ]
    ];

    public array $searchTerms = [
        'item_no',
        'description',
        'location_code',
    ];


    public function __construct(MainItemShortage $model)
    {
        $this->model = $model;
    }

    public function makeQuery(): mixed
    {
        return parent::makeQuery()->with('relatedItems');
    }

    public function show($id): mixed
    {
        return $this->model->newQuery()->with('relatedItems')->findOrFail($id);
    }
}
