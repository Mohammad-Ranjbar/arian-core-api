<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->resource->id,
            'title'      => $this->resource->title,
            'code'       => $this->resource->code,
            'attributes' => $this->resource->attributes()->get()->pluck('value', 'attribute'),
        ];
    }
}
