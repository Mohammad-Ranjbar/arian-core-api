<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Element;

class ElementRepository extends BaseRepository
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
            'code' => 'required|unique:elements,code',
            'makeCodes' => 'required|exists:make_codes,id',
        ],
        'update' => [
            'title' => 'required|string',
            'title_en' => 'required|string',
            'code' => 'required|unique:elements,code,',
            'makeCodes' => 'required|exists:make_codes,id',
        ]
    ];

    /**
     * @param Element $model
     */
    public function __construct(Element $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['code'] .= request()->route()->parameter('element');
        }
    }

    public function getOptions(): array
    {

        return $this->get('id', 'asc', request()->get('limit'))->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title . ' - ' . $item->code,
            ];
        })->all();
    }
}
