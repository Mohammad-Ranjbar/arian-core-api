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
        $title = '';
        $this->resource->erpProductItem ? $title = $this->resource->erpProductItem->title : '';
        $this->resource->generalProductItem ? $title = $this->resource->generalProductItem->title . ' ' . $this->resource->generalProductItem->code : '';
        $trackable = true;
        if (in_array($this->resource->code, ['shortage', 'noLabel', 'unreadable']) && $this->resource->type == null) {
            $trackable = false;
        }

        return [
            'id' => $this->resource->id,
            'title' => $title,
            'user_id' => $this->resource->user_id,
            'type' => $this->resource->type,
            'code' => $this->resource->code,
            'item' => $this->resource->erpProductItem,
            'general_product_item_id' => $this->resource->general_product_item_id,
            'generalProductItem' => new GeneralProductItemResource($this->resource->generalProductItem),
            'user' => new UserShortResource($this->resource->user),
            'trackable' => $trackable,
            'created_at' => $this->resource->created_at,
        ];
    }
}
