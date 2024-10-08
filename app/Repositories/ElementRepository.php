<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Element;

class ElementRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title'    => 'required|string',
            'title_en' => 'required|string',
            'code'     => 'required|unique:elements,code',
        ],
        'update' => [
            'title'    => 'required|string',
            'title_en' => 'required|string',
            'code'     => 'required|unique:elements,code,',
        ]
    ];

    /**
     * @param  Element  $model
     */
    public function __construct(Element $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['code'] .= request()->route()->parameter('element');
        }
    }
    public array $searchTerms = [
        'title','title_en','code'
    ];

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
