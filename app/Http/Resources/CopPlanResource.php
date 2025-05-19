<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CopPlanResource extends JsonResource
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
            'title' => $this->resource->title,
            'carModels' => CarModelResource::collection($this->resource->carModels),
            'file_id' => $this->resource->file_id,
            'file' => FileResource::make($this->resource->file),
            'created_at' => $this->resource->created_at,
        ];
    }
}
