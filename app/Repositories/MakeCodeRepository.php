<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\MakeCode;

class MakeCodeRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title'     => 'required|unique:make_codes,title',
            'reference' => 'required',
            'users'     => 'nullable|array',
            'users.*'   => 'exists:users,id'
        ],
        'update' => [
            'title'     => 'required|unique:roles,name,',
            'reference' => 'required',
            'users'     => 'nullable|array',
            'users.*'   => 'exists:users,id'
        ]
    ];

    /**
     * @param  MakeCode  $model
     */
    public function __construct(MakeCode $model)
    {
        $this->model = $model;

        $this->rules['update']['title'] .= request()->route()->parameter('makeCode');
    }

}