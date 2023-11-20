<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->resource->id,
            'plate'              => $this->resource->plate,
            'car'                => $this->resource->car,
            'driver_id'          => $this->resource->driver_id,
            'driver'             => new DriverResource($this->resource->driver),
            'transit_company_id' => $this->resource->transit_company_id,
            'transitCompany'     => new TransitCompanyResource($this->resource->transitCompany),
            'created_at'         => $this->resource->created_at
        ];
    }
}
