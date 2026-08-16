<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlternativeResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'alternative_item_id' => $this->resource->alternative_item_id,
            'alternativeItem' => AlternativeItemResource::make($this->resource->alternativeItem),
            'item_no' => $this->resource->item_no,
            'document_number' => $this->resource->document_number,
            'counter' => $this->resource->counter,
            'production_order_number' => $this->resource->production_order_number,
            'status' => $this->resource->status,
            'send_at' => $this->resource->send_at,
            'variant_code' => $this->resource->variant_code,
            'failed_at' => $this->resource->failed_at,
            'success_at' => $this->resource->success_at,
            'user_id' => $this->resource->user_id,
            'user' => UserShortResource::make($this->resource->user),
            'data' => $this->resource->data,
        ];
    }
}
