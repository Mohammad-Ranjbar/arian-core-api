<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTorqueTestResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'value' => $this->resource->value,
            'screw_id' => $this->resource->screw_id,
            'screw' => new ScrewResource($this->resource->screw),
            'vin' => $this->resource->vin,
            'vehicle' => new VehicleResource($this->resource->vehilce),
            'user_id' => $this->resource->user_id,
            'user' => new UserShortResource($this->resource->user),
            'related' => $this->resource->childTests()->get()->pluck('value')->all(),

            'torque_meter_id' => $this->resource->torque_meter_id,
            'torqueMeter' => TorqueMeterResource::make($this->resource->torqueMeter),


            'torque_test_group_id' => $this->resource->torque_test_group_id,
            'torqueTestGroup' => TorqueTestGroupResource::make($this->resource->torqueTestGroup),


            'torque_measuring_equipment_id' => $this->resource->torque_measuring_equipment_id,
            'torqueMeasuringEquipment' => TorqueMeasuringEquipmentResource::make($this->resource->torqueMeasuringEquipment),


            'description' => $this->resource->description,

            'created_at' => $this->resource->created_at,
        ];
    }
}
