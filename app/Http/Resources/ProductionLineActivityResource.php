<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionLineActivityResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'production_line_id' => $this->resource->production_line_id,
            'station_id' => $this->resource->station_id,
            'stop_reason_id' => $this->resource->stop_reason_id,
            'stop_type_id' => $this->resource->stop_type_id,
            'description' => $this->resource->description,
            'note' => $this->resource->note,
            'start_at' => $this->resource->start_at,
            'effective_time' => $this->resource->effective_time,
            'stop_at' => $this->resource->stop_at,
            'productionLine' => new ProductionLineResource($this->resource->productionLine),
            'station' => new StationResource($this->resource->satation),
            'stopReason' => new StopReasonResource($this->resource->stopReason),
            'stopType' => new StopTypeResource($this->resource->stopType),
            'status' => $this->resource->logs()->latest()->first() ? $this->resource->logs()->latest()->first()->action : '',
            'productItems' => ErpProductItemResource::collection($this->resource->productItems),
        ];
    }
}
