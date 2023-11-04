<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Incident;

class IncidentRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title'    => 'required|string',
            'title_en' => 'required|string',
            'code'     => 'required|unique:incidents,code',
        ],
        'update' => [
            'title'    => 'required|string',
            'title_en' => 'required|string',
            'code'     => 'required|unique:incidents,code,',
        ]
    ];

    /**
     * @param  Incident  $model
     */
    public function __construct(Incident $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['code'] .= request()->route()->parameter('incident');
        }
    }

}