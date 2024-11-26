<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ColorMap;
use Illuminate\Validation\Rule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ColorMapRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|max:255',
            'code' => 'required|max:255',
            'color_code' => 'required|max:255',
        ], 'update' => [
            'title' => 'required|max:255',
            'code' => 'required|max:255',
            'color_code' => 'required|max:255',
        ]
    ];

    /**
     * @param  ColorMap  $model
     */
    public function __construct(ColorMap $model)
    {
        $this->model = $model;
    }

}
