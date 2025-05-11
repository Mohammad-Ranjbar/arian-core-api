<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Synchronous;
use App\Models\SynchronousType;

class SynchronousTypeRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'production_line_id' => 'required|exists:production_lines,id',
            'title'              => 'required|string',
            'qty'                => 'required|numeric',
            'printer_ip'         => 'required|string',
            'type'               => 'required|string',
        ], 'update' => [
            'production_line_id' => 'required|exists:production_lines,id',
            'title'              => 'required|string',
            'qty'                => 'required|numeric',
            'printer_ip'         => 'required|string',
            'type'               => 'required|string',
        ]
    ];


    public function __construct(SynchronousType $model)
    {
        $this->model = $model;
    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id): bool
    {
        if (Synchronous::query()->where('synchronous_type_id', $id)->exists()) {
            return false;
        }
        return parent::destroy($id);
    }

}
