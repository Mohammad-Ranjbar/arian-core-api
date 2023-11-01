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
            'reference' => 'required|unique:make_codes,reference',
            'title'     => 'required',
            'users'     => 'nullable|array',
            'users.*'   => 'exists:users,id'
        ],
        'update' => [
            'reference' => 'required|unique:make_codes,reference,',
            'title'     => 'required',
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

        if (request()->route()) {
            $this->rules['update']['reference'] .= request()->route()->parameter('make_code');
        }
    }


}