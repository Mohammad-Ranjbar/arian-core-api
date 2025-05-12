<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarcodeForPoliceResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'vin' => $this->resource->vin,
            'makeCode' => $this->resource->vehicle->makeCode->title,
            'carModel' => $this->resource->vehicle->carModel->title,
            'saleDate' => $this->resource->vehicle->Police_Inspection_Date,
            'buyDate' => $this->resource->created_at,
            'color' => $this->resource->vehicle->Color_Code,
            'code' => $this->resource->code,
            'user' => $this->resource->user->name,
            'created_at' => $this->resource->created_at,
        ];
    }
}
