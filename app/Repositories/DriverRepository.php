<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Driver;

class DriverRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'name'               => 'required|string',
            'national_code'      => 'required|string|digits:10|unique:drivers',
            'phone'              => 'required|digits:11|starts_with:09|unique:drivers',
            'bank_information'   => 'nullable|string',
        ], 'update' => [
            'name'               => 'required|string',
            'national_code'      => 'required|string|digits:10|unique:drivers,national_code,',
            'phone'              => 'required|digits:11|starts_with:09|unique:drivers,phone,',
            'bank_information'   => 'nullable|string',
        ]
    ];

    /**
     * @param  Driver  $model
     */
    public function __construct(Driver $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['national_code'] .= request()->route()->parameter('driver');
            $this->rules['update']['phone'] .= request()->route()->parameter('driver');
        }
    }

}