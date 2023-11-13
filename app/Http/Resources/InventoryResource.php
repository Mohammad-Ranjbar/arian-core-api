<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->resource->id,
            'title'        => $this->resource->title,
            'make_code_id' => $this->resource->make_code_id,
            'code'         => $this->resource->code,
            'makeCode'     => new MakeCodeResource($this->resource->makeCode)
        ];
    }
}
