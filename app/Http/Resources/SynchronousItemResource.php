<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SynchronousItemResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'synchronous_id' => $this->resource->synchronous_id,
            'vin' => $this->resource->vin,
            'vehicle' => new VehicleResource($this->resource->vehicle),
        ];
    }
}
