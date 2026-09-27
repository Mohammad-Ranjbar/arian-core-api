<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ErpProductItem;

class ErpProductItemRepository extends BaseRepository
{

    public array $searchTerms = [
        'title','reference','description'
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [

        ],
        'update' => [

        ]
    ];

    /**
     * @param  ErpProductItem  $model
     */
    public function __construct(ErpProductItem $model)
    {
        $this->model = $model;
    }

    public function getOptions(): array
    {
        return $this->get('id','asc',10)->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title . ' - ' . $item->reference,
                'code' => $item->reference,
            ];
        })->all();
    }
}
