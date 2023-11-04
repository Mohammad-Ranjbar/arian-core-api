<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleIncidentResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->resource->id,
            'type'                   => $this->resource->type,
            'intensity'              => $this->resource->intensity,
            'method'                 => $this->resource->method,
            'description'            => $this->resource->description,
            'user_id'                => $this->resource->user_id,
            'production_tracking_id' => $this->resource->production_tracking_id,
            'element_id'             => $this->resource->element_id,
            'incident_id'            => $this->resource->incident_id,
            'vin'                    => $this->resource->vin,
            'checked_at'             => $this->resource->checked_at,
            'vehicle'                => $this->resource->vehicle,
            'user'                   => $this->resource->user,
            'incident'               => $this->resource->incident,
            'element'                => $this->resource->element,
            'productionTracking'     => $this->resource->productionTracking,

        ];
    }
}
