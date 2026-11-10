<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'vin' => $this->resource->vin,
            'production_order_number' => $this->resource->production_order_number,
            'variant_code' => $this->resource->variant_code,
            'due_date' => $this->resource->due_date?->format('Y-m-d'),
            'is_complete' => $this->resource->is_complete,
            'car_model_id' => $this->resource->car_model_id,
            'make_code_id' => $this->resource->make_code_id,
            'user_id' => $this->resource->user_id,
            'data' => $this->resource->data,
            'erp_response' => $this->resource->erp_response,
            'make_code' => $this->whenLoaded('makeCode', fn () => [
                'id' => $this->resource->makeCode->id,
                'title' => $this->resource->makeCode->title,
                'reference' => $this->resource->makeCode->reference,
            ]),
            'car_model' => $this->whenLoaded('carModel', fn () => [
                'id' => $this->resource->carModel->id,
                'title' => $this->resource->carModel->title,
                'reference' => $this->resource->carModel->reference,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->resource->user->id,
                'name' => $this->resource->user->name,
            ]),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
