<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'vin'                     => $this->resource->vin,
            'grade'                   => $this->resource->grade,
            'production_order_number' => $this->resource->production_order_number,
            'engine_no'               => $this->resource->engine_no,
            'cabin_no'                => $this->resource->cabin_no,
            'grade_description'       => $this->resource->grade_description,
            'qc_description'          => $this->resource->qc_description,
            'qc_status'               => $this->resource->qc_status,
            'is_block'                => $this->resource->blockItems()->whereNull('unblocked_at')->exists(),
            'makeCode'                => [
                'id'        => (int) $this->resource->makeCode->id,
                'title'     => $this->resource->makeCode->title,
                'reference' => $this->resource->makeCode->reference,
            ],
            'carModel'                => [
                'id'        => (int) $this->resource->carModel->id,
                'title'     => $this->resource->carModel->title,
                'reference' => $this->resource->carModel->reference,
            ],
        ];
    }
}
