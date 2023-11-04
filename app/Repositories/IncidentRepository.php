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
            'title'    => 'required|unique:incidents,title',
            'title_en' => 'required|unique:incidents,title_en',
            'code'     => 'required|unique:incidents,code',
        ],
        'update' => [
            'title'    => 'required|unique:incidents,title,',
            'title_en' => 'required|unique:incidents,title_en,',
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
            $this->rules['update']['title'] .= request()->route()->parameter('incident');
            $this->rules['update']['title_en'] .= request()->route()->parameter('incident');
            $this->rules['update']['code'] .= request()->route()->parameter('incident');
        }
    }

}