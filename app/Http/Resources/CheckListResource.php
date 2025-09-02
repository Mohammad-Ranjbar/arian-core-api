<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckListResource extends JsonResource
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
            'type' => $this->resource->type,
            'elements' => ElementResource::collection($this->resource->elements),
            'carModels' => CarModelResource::collection($this->resource->carModels),
            'created_at' => $this->resource->created_at,
        ];
    }
}
