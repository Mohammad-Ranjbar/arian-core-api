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
            'type' => 'required|string',
            'text' => 'required',
        ], 'update' => [
            'type' => 'required|string',
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