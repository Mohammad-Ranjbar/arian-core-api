<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScrewResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->resource->id,
            'title' => $this->resource->title,
            'min' => $this->resource->min,
            'max' => $this->resource->max,
            'torque_measuring_equipment_id' => $this->resource->torque_measuring_equipment_id,
            'salon_id' => $this->resource->salon_id,
            'salon' => SalonResource::make($this->resource->salon),
            'torqueMeasuringEquipment' => TorqueMeasuringEquipmentResource::make($this->resource->torque_measuring_equipment),

            'carModels' => $this->resource->carModels()->get()->map(function ($carModel) {
                return [
                    'id'                 => $carModel->id,
                    'title'              => $carModel->title,
                    'reference'          => $carModel->reference,
                ];
            })
        ];
    }
}
