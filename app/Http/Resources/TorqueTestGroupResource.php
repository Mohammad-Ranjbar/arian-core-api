<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TorqueTestGroupResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'date' => $this->resource->date,
            'carModel' => CarModelResource::make($this->resource->carModel),
            'car_model_id' => $this->resource->car_model_id,
            'torqueMeters' => TorqueMeterResource::collection($this->resource->torqueMeters),
            'user_id' => $this->resource->user_id,
            'user' => UserResource::make($this->resource->user),
            'created_at' => $this->resource->created_at,
        ];
    }
}
