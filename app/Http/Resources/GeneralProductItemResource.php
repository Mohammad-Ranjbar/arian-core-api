<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralProductItemResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'code' => $this->resource->code,
            'created_at' => $this->resource->created_at,
            'carModels' => CarModelResource::collection($this->resource->carModels),
            'height' => $this->resource->height,
            'priority' => $this->resource->priority,
        ];
    }
}
