<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarShortResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->resource->id,
            'name'          => $this->resource->driver->name,
            'national_code' => $this->resource->driver->national_code,
            'phone'         => $this->resource->driver->phone,
        ];
    }
}
