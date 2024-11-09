<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Pallet;

class PalletRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'pallet_type_id' => 'required|integer|exists:pallet_types,id',
        ], 'update' => [
            'title' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'pallet_type_id' => 'required|integer|exists:pallet_types,id',
        ]
    ];

    /**
     * @param  Pallet  $model
     */
    public function __construct(Pallet $model)
    {
        $this->model = $model;
    }

}
