<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Incident;

class IncidentRepository extends BaseRepository
{
    public array $searchTerms = [
        'title', 'title_en', 'code'
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string',
            'title_en' => 'required|string',
            'code' => 'required|unique:incidents,code',
            'makeCodes' => 'required|exists:make_codes,id',
        ],
        'update' => [
            'title' => 'required|string',
            'title_en' => 'required|string',
            'code' => 'required|unique:incidents,code,',
            'makeCodes' => 'required|exists:make_codes,id',
        ]
    ];

    /**
     * @param Incident $model
     */
    public function __construct(Incident $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['code'] .= request()->route()->parameter('incident');
        }
    }

    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title . ' - ' . $item->code,
            ];
        })->all();
    }
}
