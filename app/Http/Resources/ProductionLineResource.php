<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionLineResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => (int) $this->resource->id,
            'title'    => $this->resource->title,
            'salon_id' => $this->resource->salon_id
        ];
    }
}
