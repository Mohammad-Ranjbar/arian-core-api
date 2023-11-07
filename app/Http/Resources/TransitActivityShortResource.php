<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransitActivityShortResource extends JsonResource
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
            'request_no'             => $this->resource->request_no,
            'priority'               => $this->resource->priority,
            'created_at'             => $this->resource->created_at,
        ];
    }
}
