<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\SmsService;

class SmsServiceRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'text' => 'required',
        ], 'update' => [
            'text' => 'required',
        ]
    ];

    /**
     * @param  SmsService  $model
     */
    public function __construct(SmsService $model)
    {
        $this->model = $model;
    }

}