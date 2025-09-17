<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PDIResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'user' => UserShortResource::make($this->resource->user),
            'vin' => $this->resource->vin,
            'vehicle' => VehicleResource::make($this->resource->vehicle),
            'pdi_result' => $this->resource->pdi_result,
            'pdi_result_description' => $this->resource->pdi_result_description,
            'pdi_result_description_2' => $this->resource->pdi_result_description_2,
            'model_year' => $this->resource->model_year,
            'engine_no' => $this->resource->engine_no,
            'police_inspection_date' => $this->resource->police_inspection_date,
            'created_at' => $this->resource->created_at,
        ];
    }
}
