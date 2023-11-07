<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransitStatusLogResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->resource->id,
            'user_id'             => $this->resource->user_id,
            'status'              => $this->resource->status,
            'created_at'          => $this->resource->created_at,
            'transit_activity_id' => $this->resource->transit_activity_id,
            'user'                => new UserShortResource($this->resource->user),
            'transitActivity'     => new TransitActivityShortResource($this->resource->transitActivity)
        ];
    }
}
