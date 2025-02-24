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
            'cross_reference' => $this->resource->cross_reference,
            'erp_product_item_id' => $this->resource->erp_product_item_id,
            'erpProductItem' => new ErpProductItemResource($this->resource->erpProductItem),
            'number' =>$this->resource->number,
            'synchronousType' => new SynchronousTypeResource($this->resource->synchronousType),
            'items' =>  SynchronousItemResource::collection($this->resource->SynchronousItems),
        ];
    }
}
