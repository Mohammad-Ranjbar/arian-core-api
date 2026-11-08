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
            'id' => $this->resource->id,
            'car_model_id' => $this->resource->car_model_id,
            'element_id' => $this->resource->element_id,
            'incident_id' => $this->resource->incident_id,
            'carModel' => [
                'id' => $this->resource->carModel->id,
                'title' => $this->resource->carModel->title,
            ],
            'element' => [
                'id' => $this->resource->element->id,
                'title' => $this->resource->element->title,
            ],
            'incident' => [
                'id' => $this->resource->incident->id,
                'title' => $this->resource->incident->title,
            ],

            'model_type' => $this->resource->model?->getTable() ?? '',
            'model_id' => $this->resource->model?->id ?? '',
            'source' => $this->resource->model?->title ?? '',
        ];
    }
}
