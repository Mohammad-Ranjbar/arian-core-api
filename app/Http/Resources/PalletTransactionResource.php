<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PalletTransactionResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'pallet_id' => $this->resource->pallet_id,
            'location_id' => $this->resource->location_id,
            'qty' => $this->resource->qty,
            'created_at' => $this->resource->created_at,
        ];
    }
}
