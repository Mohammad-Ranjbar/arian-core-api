<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Dealer;

class DealerRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title'        => 'required',
            'code'         => 'required',
            'make_code_id' => 'required|exists:make_codes,id',
        ], 'update' => [
            'title'        => 'required',
            'code'         => 'required',
            'make_code_id' => 'required|exists:make_codes,id',
        ]
    ];

    /**
     * @param  Dealer  $model
     */
    public function __construct(Dealer $model)
    {
        $this->model = $model;
    }

}