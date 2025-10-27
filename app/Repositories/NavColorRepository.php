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
            'code' => ['required', 'string'],
            'car_model_id' => ['required','exists:car_models,id',Rule::unique('nav_colors')->where('code', request()->input('code'))],
        ];
    }

    public function getUpdateRules(): array
    {
        return [
            'color' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string'],
            'car_model_id' => ['required','exists:car_models,id',Rule::unique('nav_colors')->where('code', request()->input('code'))],
        ];
    }

}
