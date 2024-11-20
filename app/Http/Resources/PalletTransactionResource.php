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
            'pallet' => new PalletResource($this->resource->pallet),
            'location_id' => $this->resource->location_id,
            'location' => new LocationResource($this->resource->location),
            'user' => new UserResource($this->resource->user),
            'qty' => $this->resource->qty,
            'type' => $this->resource->type,
            'description' => $this->resource->description,
            'created_at' => $this->resource->created_at,
        ];
    }
}
