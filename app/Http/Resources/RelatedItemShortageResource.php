<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelatedItemShortageResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->resource->id,
            'main_item_shortage_id' => $this->resource->main_item_shortage_id,
            'item_no'               => $this->resource->item_no,
            'description'           => $this->resource->description,
            'created_at'            => $this->resource->created_at,
        ];
    }
}
