<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarModelResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->resource->id,
            'title'              => $this->resource->title,
            'reference'          => $this->resource->reference,
            'production_line_id' => (int) $this->resource->production_line_id,
            'productionLines'    => $this->resource->productionLines->pluck('id')->all()
        ];
    }
}
