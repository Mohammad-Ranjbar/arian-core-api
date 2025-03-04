<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SynchronousTypeItemResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'car_model_id' => $this->resource->car_model_id,
            'carModel' =>new CarModelResource($this->resource->carModel),
            'cross_reference' => $this->resource->cross_reference,
            'erp_product_item_id' => $this->resource->erp_product_item_id,
            'is_active' => $this->resource->is_active,
            'synchronous_type_id' => $this->resource->synchronous_type_id,
            'synchronousType' => new SynchronousTypeResource($this->resource->synchronousType),
            'created_at' => $this->resource->created_at,
        ];
    }
}
