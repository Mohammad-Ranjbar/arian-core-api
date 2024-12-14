<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpServiceItemResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'reference' => $this->resource->reference,
            'description' => $this->resource->description,
            'location_title' => $this->resource->location_title,
        ];
    }
}
