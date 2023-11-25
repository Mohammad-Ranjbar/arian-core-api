<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\File;

class FileRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [

        ], 'update' => [

        ]
    ];

    /**
     * @param  File  $model
     */
    public function __construct(File $model)
    {
        $this->model = $model;
    }

}