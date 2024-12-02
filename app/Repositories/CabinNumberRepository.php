<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\CabinNumber;

class CabinNumberRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'body_option_chr' => 'required|exists:body_options,code',
        ], 'update' => [
            'body_option_chr' => 'required|exists:body_options,code',
        ]
    ];

    /**
     * @param CabinNumber $model
     */
    public function __construct(CabinNumber $model)
    {
        $this->model = $model;
    }

}
