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
            'screw' => new ScrewResource($this->resource->screw),
            'vin' => $this->resource->vin,
            'vehicle' => new VehicleResource($this->resource->vehilce),
            'user_id' => $this->resource->user_id,
            'user' => new UserShortResource($this->resource->user),
            'related' => $this->resource->childTests()->get()->pluck('value')->all(),
            'created_at' => $this->resource->created_at,
        ];
    }
}
