<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccompanyingItemResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->resource->id,
            'title'     => $this->resource->title,
            'carModels' => CarModelResource::collection($this->resource->carModels),
        ];
    }
}
