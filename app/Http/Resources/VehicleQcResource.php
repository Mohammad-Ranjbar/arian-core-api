<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleQcResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'user' => $this->resource->user ? $this->resource->user->name : null,
            'make_code' => $this->resource->model->makeCode->title,
            'car_model' => $this->resource->model->carModel->title,
            'vin' => $this->resource->model->vin,
            'qc' => $this->resource->model->qc_status,
            'created_at' => $this->resource->created_at,
        ];
    }
}
