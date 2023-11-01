<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkGroupCenterResource extends JsonResource
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
            'production_line_id' => $this->resource->production_line_id
        ];
    }
}
