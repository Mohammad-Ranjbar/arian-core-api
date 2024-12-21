<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->resource->id,
            'key' => $this->resource->key,
            'value' => $this->resource->value,
            'users' =>  UserShortResource::collection($this->resource->users),
            'created_at' => $this->resource->created_at,
        ];
    }
}
