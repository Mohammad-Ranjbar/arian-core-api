<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CabinNumberResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'used' => $this->resource->used,
            'user' => UserShortResource::make($this->resource->user),
            'carModel' => CarModelResource::make($this->resource->carModel),
            'cabinNumber' => $this->resource->cabinNumber,
            'created_at' => $this->resource->created_at,

        ];
    }
}
