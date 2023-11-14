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
            'id'         => $this->resource->id,
            'car_model'  => $this->resource->carModel->title,
            'element'    => $this->resource->element->title,
            'incident'   => $this->resource->incident->title,
            'model_type' => $model ? $model->getTable() : '',
            'model_id'   => $model ? $model->id : '',
            'source'     => $model ? $model->title : ''
        ];
    }
}
