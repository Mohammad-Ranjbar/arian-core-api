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
        $query = $this->model->newQuery();
        if (request()->filled('make_code_id')) {
            $query = $query->whereHas('makeCodes', function ($makeCode){
                $makeCode->where('id', request()->input('make_code_id'));
            });
        }
        return $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title . ' - ' . $item->code,
            ];
        })->all();
    }
}
