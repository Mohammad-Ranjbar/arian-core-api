<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Station;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class StationRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title'                => 'required|string',
            'code'                 => 'nullable|string',
            'work_group_center_id' => 'required|exists:work_group_centers,id',
            'on_enter'             => 'nullable|boolean',
            'on_exit'              => 'nullable|boolean',
        ],
        'update' => [
            'title'                => 'required|string',
            'code'                 => 'nullable|string',
            'work_group_center_id' => 'required|exists:work_group_centers,id',
            'on_enter'             => 'nullable|boolean',
            'on_exit'              => 'nullable|boolean',
        ]
    ];

    /**
     * @param  Station  $model
     */
    public function __construct(Station $model)
    {
        $this->model = $model;
    }


    /**
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getOptions(): array
    {
        return $this->get('work_group_center_id')->map(function ($item) {
            return [
                'id'    => $item->id,
                'title' => $item->workGroupCenter ? $item->workGroupCenter->title.' - '.$item->title : $item->title,
            ];
        })->all();
    }

}