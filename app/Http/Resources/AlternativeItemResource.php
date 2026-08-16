<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlternativeItemResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->resource->id,
            'alternative_category_id' => $this->resource->alternative_category_id,
            'erp_product_item_id' => $this->resource->erp_product_item_id,
            'erpProductItem' => ErpPRoductItemResource::make($this->resource->erpProductItem),
            'packing_qty' => $this->resource->packing_qty,
            'user_id' => $this->resource->user_id,
            'item_no' => $this->resource->item_no,
            'created_at' => $this->resource->created_at,
        ];
    }
}
