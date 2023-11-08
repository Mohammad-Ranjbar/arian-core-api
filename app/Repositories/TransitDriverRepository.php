<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\TransitDriver;

class TransitDriverRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'name'               => 'required|string',
            'national_code'      => 'required|string|digits:10|unique:transit_drivers',
            'phone'              => 'required|digits:11|starts_with:09|unique:transit_drivers',
            'bank_information'   => 'nullable|string',
            'plate'              => 'required|json',
            'car'                => 'required|string',
            'transit_company_id' => 'required|exists:transit_companies,id',
        ], 'update' => [
            'name'               => 'required|string',
            'national_code'      => 'required|string|digits:10|unique:transit_drivers,national_code,',
            'phone'              => 'required|digits:11|starts_with:09|unique:transit_drivers,national_code,',
            'bank_information'   => 'nullable|string',
            'plate'              => 'required|json',
            'car'                => 'required|string',
            'transit_company_id' => 'required|exists:transit_companies,id',
        ]
    ];

    /**
     * @param  TransitDriver  $model
     */
    public function __construct(TransitDriver $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['national_code'] .= request()->route()->parameter('transit_driver');
            $this->rules['update']['phone'] .= request()->route()->parameter('transit_driver');
        }
    }

}