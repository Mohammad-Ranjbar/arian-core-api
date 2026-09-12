<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MainItemShortageResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->resource->id,
            'location_code'       => $this->resource->location_code,
            'car_model_id'        => $this->resource->car_model_id,
            'car_model_reference' => $this->resource->car_model_reference,
            'item_no'             => $this->resource->item_no,
            'description'         => $this->resource->description,
            'version'             => $this->resource->version,
            'is_active'           => $this->resource->is_active,
            'main_item_shortage_id' => $this->resource->main_item_shortage_id,
            'related_items'       => RelatedItemShortageResource::collection($this->whenLoaded('relatedItems')),
            'created_at'          => $this->resource->created_at,
        ];
    }
}
