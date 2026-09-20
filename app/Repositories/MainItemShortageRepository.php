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
            'routing_link_code'         => 'required|string',
        ]
    ];

    public array $searchTerms = [
        'item_no',
        'description',
        'location_code',
    ];

    /**
     * @param MainItemShortage $model
     */
    public function __construct(MainItemShortage $model)
    {
        $this->model = $model;
    }

    /**
     * A record that has been forked from is a superseded snapshot, so the list
     * shows the leaf of each chain — the draft or active record in play.
     */
    public function makeQuery(): mixed
    {
        return parent::makeQuery()->with('relatedItems');
    }

    public function show($id): mixed
    {
        return $this->model->newQuery()->with('relatedItems')->findOrFail($id);
    }
}
