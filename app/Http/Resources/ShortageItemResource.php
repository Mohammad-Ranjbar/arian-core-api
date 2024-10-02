<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortageItemResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'vin' => $this->resource->vin,
            'erp_product_item_id' => $this->resource->erp_product_item_id,
            'erpProductItem' => new ErpProductItemResource($this->resource->erpProductItem),
            'user' => new UserResource($this->resource->user),
            'status' => $this->resource->status,
            'done_by' => $this->resource->actionLog?->user->name ?? null,
            'done_at' => $this->resource->actionLog?->created_at ?? null,
            'created_at' => $this->resource->created_at,
        ];
    }
}
