<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TorqueCoefficientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->resource->id,
            'low_1'     => $this->resource->low_1,
            'low_2'     => $this->resource->low_2,
            'low_3'     => $this->resource->low_3,
            'high_1'    => $this->resource->high_1,
            'high_2'    => $this->resource->high_2,
            'high_3'    => $this->resource->high_3,
            'is_active' => $this->resource->is_active,
            'created_at'=> $this->resource->created_at,
        ];
    }
}
