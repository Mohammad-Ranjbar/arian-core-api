<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GatePermitResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->resource->id,
            'type'       => $this->resource->type,
            'deleted_at' => $this->resource->deleted_at
        ];
    }
}
