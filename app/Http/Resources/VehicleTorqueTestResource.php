<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTorqueTestResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'value' => $this->resource->value,
            'screw_id' => $this->resource->screw_id,
            'vin' => $this->resource->vin,
            'created_at' => $this->resource->created_at,
        ];
    }
}
