<?php

namespace App\Http\Resources;

use App\Models\RegisterShortage;
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
            'type' => match ($this->resource->type) {
                RegisterShortage::TYPE_MAIN => 'اصلی',
                RegisterShortage::TYPE_SINGLE => 'تکی',
                default => $this->resource->type,
            },
            'qty' => $this->resource->qty,
            'work_center' => $this->resource->work_center,
            'status' => match ($this->resource->status) {
                RegisterShortage::STATUS_RESOLVED => 'رفع شده',
                RegisterShortage::STATUS_HAS_SHORTAGE => 'دارای کسری',
                default => $this->resource->status,
            },
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
            'children' => $this->resource->register_shortage_id === null
                ? $this->whenLoaded('children', fn () => self::collection($this->resource->children))
                : [],
            'is_certified' => $this->resource->is_certified,
            'resolved_at' => $this->resource->resolved_at,
            'resolvedBy' => UserShortResource::make($this->resource->resolvedBy),
            'is_84' => $this->resource->is84,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
