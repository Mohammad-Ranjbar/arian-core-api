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
            'title'    => 'required|unique:elements,title',
            'title_en' => 'required|unique:elements,title_en',
            'code'     => 'required|unique:elements,code',
        ],
        'update' => [
            'title'    => 'required|unique:elements,title,',
            'title_en' => 'required|unique:elements,title_en,',
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
            $this->rules['update']['title'] .= request()->route()->parameter('element');
            $this->rules['update']['title_en'] .= request()->route()->parameter('element');
            $this->rules['update']['code'] .= request()->route()->parameter('element');
        }
    }

}