<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Barcode;

class BarcodeRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'vin'     => 'required|string|exists:vehicles,vin',
            'code'    => 'required|string|unique:barcodes,code',
        ], 'update' => [
            'vin'     => 'required|string|exists:vehicles,vin',
            'code'    => 'required|string'
        ]
    ];

    /**
     * @param  Barcode  $model
     */
    public function __construct(Barcode $model)
    {
        $this->model = $model;
    }

}
