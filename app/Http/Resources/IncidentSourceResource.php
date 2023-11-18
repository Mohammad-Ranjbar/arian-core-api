<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentSourceResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $model = $this->resource->model;
        return [
            'id'           => $this->resource->id,
            'car_model_id' => $this->resource->car_model_id,
            'element_id'   => $this->resource->element_id,
            'incident_id'  => $this->resource->incident_id,

            'carModel' => new CarModelResource($this->resource->carModel),
            'element'  => new ElementResource($this->resource->element),
            'incident' => new IncidentResource($this->resource->incident),

            'model_type' => $model ? $model->getTable() : '',
            'model_id'   => $model ? $model->id : '',
            'source'     => $model ? $model->title : ''
        ];
    }
}
