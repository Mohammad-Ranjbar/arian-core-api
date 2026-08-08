<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlternativeCategoryResource extends JsonResource
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
            'is_active' => $this->resource->is_active,
            'user_id' => $this->resource->user_id,
            'user' => USerShortResource::make($this->resource->user),
        ];
    }
}
