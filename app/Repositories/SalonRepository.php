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
            'title' => 'required|string|unique:salons,title',
        ], 'update' => [
            'title' => 'required|string|unique:salons,title,',
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