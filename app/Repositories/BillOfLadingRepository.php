<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\BillOfLading;

class BillOfLadingRepository extends BaseRepository
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
     * @param  BillOfLading  $model
     */
    public function __construct(BillOfLading $model)
    {
        $this->model = $model;
    }

}