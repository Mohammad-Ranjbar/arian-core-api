<?php

namespace App\Http\Resources;

use App\Models\NavColor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortVehicleResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'vin' => $this->resource->vin,
            'production_order_number' => $this->resource->production_order_number,
            'qc_status' => $this->resource->qc_status,
            'production_type' => $this->resource->getProductionType(),
            'color_code' => $this->resource->color_code,
            'color_code_map' => !empty($this->resource->data['ColorCode']) ? $this->resource->color_code : NavColor::query()
                ->where('car_model_id', $this->resource->car_model_id)
                ->where('color', $this->resource->color_code)
                ->first()?->getAttribute('code') ?? 'unknown',
            'make_code_title' => $this->resource->makeCode->title,
            'car_model_title' => $this->resource->carModel->title,
        ];
    }
}
