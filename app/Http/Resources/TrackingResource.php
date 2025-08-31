<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type,
            'data' => $this->resource->data,
            'user' => new UserShortResource($this->resource->user),
            'vehicle' => new VehicleResource($this->resource->vehicle),
            'temporaryProduction' => TemporaryProductionResource::make($this->resource->temporaryProduction),
            'created_at' => $this->resource->created_at,
        ];
    }
}
