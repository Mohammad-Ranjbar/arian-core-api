<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PdiCheckListResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'file_id' => $this->resource->file_id,
            'file' => FileResource::make($this->resource->file),
            'carModels' => CarModelResource::collection($this->resource->carModels),
            'created_at' => $this->resource->created_at,
        ];
    }
}
