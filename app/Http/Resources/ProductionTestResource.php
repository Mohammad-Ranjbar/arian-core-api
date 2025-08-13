<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionTestResource extends JsonResource
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
            'type' => $this->resource->type,
            'carModels' => $this->resource->carModels()->get()->pluck('id')->all(),
            'created_at' => $this->resource->created_at,
        ];
    }
}
