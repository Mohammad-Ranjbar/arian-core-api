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
        'create' => [
            'vin' => 'required|string|exists:vehicles,vin',
            'code' => ['required', 'string', 'CodeUniqueWhenDeletedAtNullRule'],
            'general_product_item_id' => 'nullable|exists:general_product_items,id',
        ], 'update' => [
            'vin' => 'required|string|exists:vehicles,vin',
            'code' => 'required|string|CodeUniqueWhenDeletedAtNullRule',
            'general_product_item_id' => 'nullable|exists:general_product_items,id',
        ]
    ];

    /**
     * @param Barcode $model
     */
    public function __construct(Barcode $model)
    {
        $this->model = $model;
    }

}
