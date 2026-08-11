<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlternativeCategoryItemResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'alternative_category_id' => $this->resource->alternative_category_id,
            'erp_product_item_id' => $this->resource->erp_product_item_id,
            'erp_product_item' => ErpProductItemResource::make($this->resource->erpProductItem),
            'packing_qty' => $this->resource->packing_qty,
            'user_id' => $this->resource->user_id,
            'user' => UserShortResource::make($this->resource->user),
            'item_no' => $this->resource->item_no,
            'created_at' => $this->resource->created_at,
        ];
    }
}
