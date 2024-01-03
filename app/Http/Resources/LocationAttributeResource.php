<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationAttributeResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->resource->id,
            'attribute'   => $this->resource->attribute,
            'value'       => $this->resource->value,
            'location_id' => $this->resource->location_id,
        ];
    }
}
