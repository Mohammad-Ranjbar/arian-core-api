<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository
{
    /**
     * @var array|string[]
     */
    public array $searchTerms = [
        'name', 'national_code', 'email', 'mobile'
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'name'          => 'required',
            'email'         => 'unique:users,email',
            'mobile'        => 'digits:11|starts_with:09|unique:users,mobile',
            'password'      => 'required|min:6',
            'avatar'        => 'nullable',
            'roles'         => 'nullable|array',
            'roles.*'       => 'exists:roles,id',
            'makeCodes'     => 'nullable|array',
            'makeCodes.*'   => 'exists:make_codes,id',
            'national_code' => 'required|string|digits:10|unique:users,national_code',
            'locations'     => 'nullable|array',
            'locations.*'   => 'nullable|exists:locations,id',
        ],
        'update' => [
            'name'          => 'required',
            'email'         => 'required|unique:users,email,',
            'mobile'        => 'digits:11|starts_with:09|required|unique:users,mobile,',
            'password'      => 'nullable|min:6',
            'avatar'        => 'nullable',
            'roles'         => 'nullable|array',
            'roles.*'       => 'exists:roles,id',
            'makeCodes'     => 'nullable|array',
            'makeCodes.*'   => 'exists:make_codes,id',
            'national_code' => 'required|string|digits:10|unique:users,national_code,',
            'locations'     => 'nullable|array',
            'locations.*'   => 'nullable|exists:locations,id',
        ]
    ];

    /**
     * @param  User  $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['email'] .= request()->route()->parameter('user');
            $this->rules['update']['mobile'] .= request()->route()->parameter('user');
            $this->rules['update']['national_code'] .= request()->route()->parameter('user');
        }
    }

    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->name . ' - ' . $item->mobile,
            ];
        })->all();
    }

}
