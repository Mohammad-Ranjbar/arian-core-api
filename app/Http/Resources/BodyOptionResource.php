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
            'carModels' => $this->resource->carModels()->select(['id','title'])->get(),
            'created_at' => $this->resource->created_at,
        ];
    }
}
