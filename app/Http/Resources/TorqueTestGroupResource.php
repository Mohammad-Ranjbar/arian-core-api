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
            'title' => $this->resource->title,
            'register_date' => $this->resource->register_date,
            'carModels' => CarModelResource::collection($this->resource->carModels),
            'torqueMeasuringEquipments' => TorqueMeasuringEquipmentResource::collection($this->resource->torqueMeasuringEquipments),
            'created_at' => $this->resource->created_at,
        ];
    }
}
