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
            'production_order_number' => $this->resource->production_order_number,
            'engine_no'               => $this->resource->engine_no,
            'cabin_no'                => $this->resource->cabin_no,
            'make_code'               => [
                'title'     => $this->resource->makeCode->title,
                'reference' => $this->resource->makeCode->reference,
            ],
            'car_model'               => [
                'title'     => $this->resource->carModel->title,
                'reference' => $this->resource->carModel->reference,
            ],
        ];
    }
}
