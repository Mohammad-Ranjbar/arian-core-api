<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SynchronousResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'capacity' => $this->resource->capacity,
            'synchronous_type_id' => $this->resource->synchronous_type_id,
            'counter' => $this->resource->counter,
            'number' =>$this->resource->number,
            'synchronousType' => new SynchronousTypeResource($this->resource->synchronousType),
            'items' => new SynchronousItemResource($this->resource->items),
        ];
    }
}
