<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\YearMap;

class YearMapRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'year' => 'required|integer|min:1|unique:year_maps,year',
            'chr' => 'required|string|max:255'
        ], 'update' => [
            'year' => 'required|integer|min:1|unique:year_maps,year,',
            'chr' => 'required|string|max:255'
        ]
    ];

    /**
     * @param YearMap $model
     */
    public function __construct(YearMap $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['year'] .= request()->route()->parameter('year_map');
        }
    }

}
