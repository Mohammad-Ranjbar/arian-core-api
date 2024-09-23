<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionTestResultResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->resource->id,
            'vin' => $this->resource->vin,
            'user_id' => $this->resource->user_id,
            'production_test_id' => $this->resource->production_test_id,
            'description' => $this->resource->description,
            'files' => $this->resource->files,
            'vehicle' => new VehicleResource($this->resource->vehicle),
            'user' => new UserResource($this->resource->user),
            'productionTest' => new ProductionTestResource($this->resource->production_test),
        ];
    }
}
