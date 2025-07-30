<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VinEngineResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->resource->id,
            'vin' => $this->resource->vin,
            'engine_no' => $this->resource->engine_no,
            'production_order_number' => $this->resource->production_order_number,
            'created_at' => $this->resource->created_at,
        ];
    }
}
