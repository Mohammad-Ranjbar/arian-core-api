<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarModelFileResource extends JsonResource
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
            'file' => FileResource::make($this->resource->file),
            'file_id' => $this->resource->file_id,
            'created_at' => $this->resource->created_at,
        ];
    }
}
