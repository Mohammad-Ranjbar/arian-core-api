<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionControlResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'makeCodes' => MakeCodeResource::collection($this->resource->makeCodes),
            'created_at' => $this->resource->created_at,

        ];
    }
}
