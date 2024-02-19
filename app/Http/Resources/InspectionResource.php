<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'vin'     => $this->resource->vin,
            'data'    => $this->resource->data,
            'user'    => new UserShortResource($this->resource->user),
        ];
    }
}
