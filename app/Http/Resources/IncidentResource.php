<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->resource->id,
            'title'    => $this->resource->title,
            'title_en' => $this->resource->title_en,
            'code'     => $this->resource->code,
        ];
    }
}
