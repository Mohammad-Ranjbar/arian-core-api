<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransitActivityResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->resource->id,
            'user_id'                => $this->resource->user_id,
            'user'                   => new UserShortResource($this->resource->user),
            'transitSource'          => new TransitSourceResource($this->resource->transitSource),
            'transitDestination'     => new TransitDestinationResource($this->resource->transitDestination),
            'bill_of_lading'         => $this->resource->bill_of_lading,
            'transit_source_id'      => $this->resource->transit_source_id,
            'transit_driver_id'      => $this->resource->transit_driver_id,
            'transitDriver'          => new CarResource($this->resource->transitDriver),
            'transit_destination_id' => $this->resource->transit_destination_id,
            'request_no'             => $this->resource->request_no,
            'status'                 => $this->resource->status,
            'priority'               => $this->resource->priority,
            'container'              => $this->resource->container,
            'cottage'                => $this->resource->cottage,
            'weight'                 => $this->resource->weight,
            'batch'                  => $this->resource->batch,
            'description'            => $this->resource->description,
            'exit_by'                => $this->resource->exit_by,
            'created_at'             => $this->resource->created_at,
        ];
    }
}
