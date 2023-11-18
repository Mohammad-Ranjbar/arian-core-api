<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Container;

class ContainerRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'code' => 'required|unique:containers,code'
        ], 'update' => [
            'code' => 'required|unique:containers,code,'
        ]
    ];

    /**
     * @param  Container  $model
     */
    public function __construct(Container $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['code'] .= request()->route()->parameter('container');
        }
    }

}