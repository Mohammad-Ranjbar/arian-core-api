<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'name'             => $this->resource->name,
            'phone'            => $this->resource->phone,
            'national_code'    => $this->resource->national_code,
            'bank_information' => $this->resource->bank_information,
        ];
    }
}
