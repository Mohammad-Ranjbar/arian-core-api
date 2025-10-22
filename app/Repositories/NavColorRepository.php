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
            'car_model_id' => ['required','exists:car_models,id'],
        ];
    }

    public function getUpdateRules(): array
    {
        return [
            'color' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string',         Rule::unique('nav_colors')->where('color', request()->input('color'))->ignore(request()->route('nav_color'))],
            'car_model_id' => ['required','exists:car_models,id'],
        ];
    }

}
