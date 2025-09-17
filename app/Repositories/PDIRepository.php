<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\PDI;

class PDIRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'pdi_result' => 'required|string|max:255',
            'pdi_result_description' => 'required|string|max:250',
            'pdi_result_description_2' => 'nullable|string|max:100',
            'model_year' => 'required|string|max:255',
            'engine_no' => 'required|string|max:255',
            'police_inspection_date' => 'required|string|max:255'
        ], 'update' => [

        ]
    ];

    /**
     * @param PDI $model
     */
    public function __construct(PDI $model)
    {
        $this->model = $model;
    }

}
