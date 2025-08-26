<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemporaryProductionResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'production_order_number' => $this->resource->production_order_number,
            'temp_vin' => $this->resource->temp_vin,
            'user_id' => $this->resource->user_id,
            'user' => UserShortResource::make($this->resource->user),
            'created_at' => $this->resource->created_at,
        ];
    }
}
