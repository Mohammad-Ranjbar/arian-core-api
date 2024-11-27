<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BodyOptionResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->resource->id,
            'title' => $this->resource->title,
            'code' => $this->resource->code,
            'carModels' => CarModelResource::collection($this->resource->carModels),
            'created_at' => $this->resource->created_at,
        ];
    }
}
