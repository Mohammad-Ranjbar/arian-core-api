<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleLocationResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->resource->id,
            'title'    => $this->resource->title,
            'code'     => $this->resource->code,
            'makeCode' => new MakeCodeResource($this->resource->makeCode),
            'users'    => UserShortResource::collection($this->resource->users)
        ];
    }
}
