<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BasketResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'user_id' => $this->resource->user_id,
            'status' => $this->resource->status,
            'description' => $this->resource->descriotion,
            'user' => new UserShortResource($this->resource->user)
        ];
    }
}
