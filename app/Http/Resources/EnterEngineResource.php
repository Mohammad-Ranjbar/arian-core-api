<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterEngineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'engine_no' => $this->resource->engine_no,
            'production_line_id' => $this->resource->production_line_id,
            'productionLine' => ProductionLineResource::make($this->resource->productionLine),
            'user_id' => $this->resource->user_id,
            'vin' => $this->resource->vin,
            'user' => UserResource::make($this->resource->user),
            'created_at' => $this->resource->created_at,
        ];
    }
}
