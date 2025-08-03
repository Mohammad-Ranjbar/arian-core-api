<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangeVinResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'vin' => $this->resource->vin,
            'old_vin' => $this->resource->old_vin,
            'production_order_number' => $this->resource->production_order_number,
            'data' => $this->resource->data,
            'created_at' => $this->resource->created_at,
        ];
    }
}
