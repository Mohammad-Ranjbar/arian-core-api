<?php

namespace App\Http\Resources;

use App\Models\File;
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
        $urls = [];
        foreach ((array)json_decode($this->resource->files) as $file){
            $data = File::query()->where('uuid', $file)->first();
            $urls[] = route('dl',['path' => $data->path]);
        }
        return [
            'id'    => $this->resource->id,
            'vin' => $this->resource->vin,
            'user_id' => $this->resource->user_id,
            'production_test_id' => $this->resource->production_test_id,
            'description' => $this->resource->description,
            'files' => $this->resource->files,
            'urls' => $urls,
            'vehicle' => new VehicleResource($this->resource->vehicle),
            'user' => new UserResource($this->resource->user),
            'productionTest' => new ProductionTestResource($this->resource->productionTest),
            'created_at' => $this->resource->created_at,
        ];
    }
}
