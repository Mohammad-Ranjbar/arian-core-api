<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\SynchronousType;

class SynchronousTypeRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'production_line_id' => 'required|exists:production_lines,id',
            'title' => 'required|string',
            'qty' => 'required|numeric',
            'printer_ip' => 'required|string',
        ], 'update' => [
            'production_line_id' => 'required|exists:production_lines,id',
            'title' => 'required|string',
            'qty' => 'required|numeric',
            'printer_ip' => 'required|string',
        ]
    ];


    public function __construct(SynchronousType $model)
    {
        $this->model = $model;
    }

}
