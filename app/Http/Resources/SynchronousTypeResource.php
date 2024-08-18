<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SynchronousTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'production_line_id' => $this->resource->production_line_id,
            'title' => $this->resource->title,
            'qty' => $this->resource->qty,
            'productionLine' => new ProductionLineResource($this->resource->productionLine),
        ];
    }
}
