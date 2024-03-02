<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Salon;

class SalonRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title'    => 'required|string|unique:salons,title',
            'on_enter' => 'nullable|boolean',
            'on_exit'  => 'nullable|boolean',
        ], 'update' => [
            'title'    => 'required|string|unique:salons,title,',
            'on_enter' => 'nullable|boolean',
            'on_exit'  => 'nullable|boolean',
        ]
    ];

    /**
     * @param  Salon  $model
     */
    public function __construct(Salon $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('salon');
        }
    }

}