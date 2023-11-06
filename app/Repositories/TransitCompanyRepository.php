<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TransitCompany;

class TransitCompanyRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string'
        ], 'update' => [
            'title' => 'required|string'
        ]
    ];

    /**
     * @param  TransitCompany  $model
     */
    public function __construct(TransitCompany $model)
    {
        $this->model = $model;
    }

}