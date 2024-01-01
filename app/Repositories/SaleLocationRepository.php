<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\SaleLocation;
use Illuminate\Validation\Rule;

class SaleLocationRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title'        => 'required',
            'code'         => 'required',
            'make_code_id' => 'required|exists:make_codes,id',

        ], 'update' => [
            'title'        => 'required',
            'code'         => 'required',
            'make_code_id' => 'required|exists:make_codes,id',
            'users'        => 'required|array',
            'users.*'      => 'required|exists:users,id',
        ]
    ];

    /**
     * @param  SaleLocation  $model
     */
    public function __construct(SaleLocation $model)
    {
        $this->model = $model;
    }

}