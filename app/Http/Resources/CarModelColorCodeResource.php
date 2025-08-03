<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarModelColorCodeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'variant' => $this->resource->variant,
            'color_code' => $this->resource->color_code,
            'car_model_id' => $this->resource->car_model_id,
            'created_at' => $this->resource->created_at,
            'carModel' => CarModelResource::make($this->resource->carModel),
        ];
    }
}
