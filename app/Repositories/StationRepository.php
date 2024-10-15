<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Station;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class StationRepository extends BaseRepository
{
    public array $searchTerms = ['title'];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string',
            'code' => 'nullable|string',
            'work_group_center_id' => 'required|exists:work_group_centers,id',
            'on_enter' => 'nullable|boolean',
            'on_exit' => 'nullable|boolean',
        ],
        'update' => [
            'title' => 'required|string',
            'code' => 'nullable|string',
            'work_group_center_id' => 'required|exists:work_group_centers,id',
            'on_enter' => 'nullable|boolean',
            'on_exit' => 'nullable|boolean',
        ]
    ];

    /**
     * @param Station $model
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
        $data = $this->model;

        if (request()->has('station_production_line_id') || request()->get('station_production_line_id')) {
            $data = $this->model->newQuery()->whereHas('workGroupCenter', function ($query) {
                $query->where('production_line_id', request()->get('station_production_line_id'));
            });
        }

        $data = $data->orderBy('work_group_center_id')->get();
        return $data->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->workGroupCenter ? $item->workGroupCenter->title . ' - ' . $item->title : $item->title,
            ];
        })->all();
    }
}
