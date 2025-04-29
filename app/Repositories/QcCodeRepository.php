<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\QcCode;

class QcCodeRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
                'title' => 'required|string|unique:qc_codes,title',
                'item_type' => 'required|in:vehicle,body',
                'type' => 'required|in:0,1',
        ], 'update' => [
            'title' => 'required|string|unique:qc_codes,title,',
            'item_type' => 'required|in:vehicle,body',
            'type' => 'required|in:0,1',
        ]
    ];

    /**
     * @param  QcCode  $model
     */
    public function __construct(QcCode $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('qc_code');
        }
    }
    public function getOptions():array
    {
        return $this->get('id', 'asc', request()->get('limit'))->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'item_type' => $item->item_type,
            ];
        })->all();
    }

}
