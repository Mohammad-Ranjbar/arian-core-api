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
            'id'                 => $this->resource->id,
            'name'               => $this->resource->name,
            'national_code'      => $this->resource->national_code,
        ];
    }
}
