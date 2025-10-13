<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\NavColor;
use Illuminate\Validation\Rule;

class NavColorRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [


        ], 'update' => [

        ]
    ];

    /**
     * @param NavColor $model
     */
    public function __construct(NavColor $model)
    {
        $this->model = $model;
    }

    public function getCreateRules(): array
    {
        return [
            'color' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', Rule::unique('nav_colors')->where('color', request()->input('color'))],
        ];
    }

    public function getUpdateRules(): array
    {
        return [
            'color' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string',         Rule::unique('nav_colors')->where('color', request()->input('color'))],
        ];
    }

}
