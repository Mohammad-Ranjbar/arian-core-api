<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingSaleLocationResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'sale_location_title' => $this->resource->model->title,
            'sale_location_id' => $this->resource->model_id,
            'user' => $this->resource->user->name,
            'type' => $this->resource->type,
            'vin' => $this->resource->vin,
            'data' => $this->resource->data,
            'created_at' => $this->resource->created_at,
        ];
    }
}
