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
            'id'          => $this->resource->id,
            'type'        => $this->resource->type,
            'location_id' => $this->resource->location_id,
            'location'    => $this->resource->location,
            'model_type'  => $this->resource->model?->getTable() ?? 'unknown',
            'model_id'    => $this->resource->model->id ? $this->resource->model->id : $this->resource->model->vin,
            'model'       => $this->resource->model,
            'deleted_at'  => $this->resource->deleted_at
        ];
    }
}
