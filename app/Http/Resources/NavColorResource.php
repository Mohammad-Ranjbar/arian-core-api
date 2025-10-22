<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NavColorResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'color' => $this->resource->color,
            'code' => $this->resource->code,
            'car_model_id' => $this->resource->car_model_id,
            'carModel' => CarModelResource::make($this->resource->carModel),
            'created_at' => $this->resource->created_at,
        ];
    }
}
