<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralProductItemResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $carModel = null;
        if (!$request->get("resource") == "short"){
            $carModel = CarModelResource::collection($this->resource->carModels);
        }
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'carModels' => $carModel,
            'height' => $this->resource->height,
            'priority' => $this->resource->priority,
            'type' => $this->resource->type,
            'stations' => StationResource::collection($this->resource->stations),
            'part_type' => $this->resource->part_type,
            'label_status' => $this->resource->label_status,
            'hack_status' => $this->resource->hack_status,
            'count_lable' => $this->resource->count_lable,
            'created_at' => $this->resource->created_at,
        ];
    }
}
