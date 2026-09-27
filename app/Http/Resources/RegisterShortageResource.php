<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterShortageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'vin' => $this->resource->vin,
            'car_model_id' => $this->resource->car_model_id,
            'make_code_id' => $this->resource->make_code_id,
            'item_no' => $this->resource->item_no,
            'item_description' => $this->resource->item_description,
            'type' => $this->resource->type,
            'qty' => $this->resource->qty,
            'work_center' => $this->resource->work_center,
            'status' => $this->resource->status,
            'user_id' => $this->resource->user_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'vin' => $this->resource->vehicle->vin,
                'production_order_number' => $this->resource->vehicle->production_order_number,
                'engine_no' => $this->resource->vehicle->engine_no,
                'cabin_no' => $this->resource->vehicle->cabin_no,
            ]),
            'car_model' => $this->whenLoaded('carModel', fn () => [
                'id' => $this->resource->carModel->id,
                'title' => $this->resource->carModel->title,
                'reference' => $this->resource->carModel->reference,
            ]),
            'make_code' => $this->whenLoaded('makeCode', fn () => [
                'id' => $this->resource->makeCode->id,
                'title' => $this->resource->makeCode->title,
                'reference' => $this->resource->makeCode->reference,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->resource->user->id,
                'name' => $this->resource->user->name,
            ]),
            'children' => $this->resource->chilren,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
