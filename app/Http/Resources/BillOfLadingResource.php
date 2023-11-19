<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillOfLadingResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->resource->id,
            'transit_source_id'      => $this->resource->transit_source_id,
            'transit_destination_id' => $this->resource->transit_destination_id,
            'code'                   => $this->resource->code,
            'request_no'             => $this->resource->request_no,
            'priority'               => $this->resource->priority,
            'cottage'                => $this->resource->cottage,
            'weight'                 => $this->resource->weight,
            'batch'                  => $this->resource->batch,
            'car_id'                 => $this->resource->car_id,
            'transitSource'          => new TransitSourceResource($this->resource->transitSource),
            'transitDestination'     => new TransitDestinationResource($this->resource->transitDestination),
            'car'                    => new CarShortResource($this->resource->car),
            'created_at'             => $this->resource->created_at,
        ];
    }
}
