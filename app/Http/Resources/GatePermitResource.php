<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GatePermitResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->resource->id,
            'car_id' => $this->resource->car_id,
            'status' => $this->resource->status,
            'type'   => $this->resource->type,
            'car'    => new CarResource($this->resource->car)
        ];
    }
}
