<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarcodeResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'code' => $this->resource->code,
            'item' => $this->resource->erpProductItem,
            'general_product_item_id' => $this->resource->general_product_item_id,
            'generalProductItem' => new GeneralProductItemResource($this->resource->generalProductItem),
            'user' => new UserShortResource($this->resource->user)
        ];
    }
}
