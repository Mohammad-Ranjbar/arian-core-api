<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'name'     => 'required',
            'email'    => 'unique:users,email',
            'mobile'   => 'unique:users,mobile',
            'password' => 'required|min:6',
            'avatar'   => 'nullable',
        ],
        'update' => [
            'name'     => 'required',
            'email'    => 'required|unique:users,email,',
            'mobile'   => 'required|unique:users,mobile,',
            'password' => 'required|min:6',
            'avatar'   => 'nullable',
        ]
    ];

    /**
     * @param  User  $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;

        $this->rules['update']['email'] .= request()->route()->parameter('user');
        $this->rules['update']['mobile'] .= request()->route()->parameter('user');
    }

}