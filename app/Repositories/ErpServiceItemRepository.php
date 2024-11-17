<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ErpProductItem;
use App\Models\ErpServiceItem;

class ErpServiceItemRepository extends BaseRepository
{

    public array $searchTerms = [
        'title','reference','location_title'
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

    public function __construct(ErpServiceItem $model)
    {
        $this->model = $model;
    }

    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title . ' - ' . $item->reference.'-'.$item->location_title,
            ];
        })->all();
    }
}
