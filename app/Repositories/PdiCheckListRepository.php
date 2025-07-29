<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\PdiCheckList;

class PdiCheckListRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string',
            'file_id' => 'required|exists:files,uuid',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ], 'update' => [
            'title' => 'required|string',
            'file_id' => 'required|exists:files,uuid',
            'carModels' => 'required|array',
            'carModels.*' => 'required|exists:car_models,id',
        ]
    ];


    public function __construct(PdiCheckList $model)
    {
        $this->model = $model;
    }

}
