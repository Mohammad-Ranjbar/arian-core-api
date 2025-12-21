<?php

namespace App\Http\Resources;

use App\Models\VinEngine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterEngineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'counter' => $this->resource->counter,
            'engine_no' => $this->resource->engine_no,
            'production_line_id' => $this->resource->production_line_id,
            'productionLine' => ProductionLineResource::make($this->resource->productionLine),
            'user_id' => $this->resource->user_id,
            'make_code_id' => $this->resource->make_code_id,
            'vin' => $this->resource->vin,
            'user' => UserResource::make($this->resource->user),
            'make_code' => MakeCodeResource::make($this->resource->makeCode),
            'cabin_no' => VinEngine::query()->where('engine_no',$this->resource->engine_no)->first()?->cabin_no ?? null,
            'created_at' => $this->resource->created_at,
        ];
    }
}
