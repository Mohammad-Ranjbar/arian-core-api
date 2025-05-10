<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TorqueMeasuringEquipment;

class TorqueMeasuringEquipmentRepository extends BaseRepository
{
    /**
     * @var array|string[]
     */
    public array $searchTerms = [
        'title'
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required',
        ],
        'update' => [
            'title' => 'required',
        ]
    ];

    /**
     * @param TorqueMeasuringEquipment $model
     */
    public function __construct(TorqueMeasuringEquipment $model)
    {
        $this->model = $model;
    }

    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
            ];
        })->all();
    }

}
