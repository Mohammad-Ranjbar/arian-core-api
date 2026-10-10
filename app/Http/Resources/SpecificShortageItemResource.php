<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecificShortageItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'canDelete' => $this->resource->canDelete,
            'qc_code' => $this->resource->qc_code,
            'item_no' => $this->resource->item_no,
            'description' => $this->resource->description,
            'erp_product_item_id' => $this->resource->erp_product_item_id,
            'erpProductItem' => ErpProductItemResource::make(
                $this->whenLoaded('erpProductItem')
            ),
            'car_model_id' => $this->resource->car_model_id,
            'carModel' => $this->whenLoaded('carModel', fn () => [
                'id' => $this->resource->carModel->id,
                'title' => $this->resource->carModel->title,
                'reference' => $this->resource->carModel->reference,
            ]),
            'is_active' => $this->resource->is_active,
            'actionLogs' => ActionLogResource::collection($this->whenLoaded('actionLogs')),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
