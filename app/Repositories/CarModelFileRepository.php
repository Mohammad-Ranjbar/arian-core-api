<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\CarModelFile;

class CarModelFileRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:255',
            'file_id' => 'required|string|exists:files,uuid',
        ], 'update' => [
            'title' => 'required|string|max:255',
            'file_id' => 'nullable|string|exists:files,uuid',
        ]
    ];

    /**
     * @param  CarModelFile  $model
     */
    public function __construct(CarModelFile $model)
    {
        $this->model = $model;
    }

}
