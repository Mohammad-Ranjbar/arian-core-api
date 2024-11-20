<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PalletResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->resource->id,
            'title' => $this->resource->title,
            'owner' => $this->resource->owner,
            'pallet_type_id' => $this->resource->pallet_type_id,
            'palletType' => new PalletTypeResource($this->resource->palletType),
            'created_at' => $this->resource->created_at,
            'total' => $this->resource->transactions->sum('qty')
        ];
    }
}
