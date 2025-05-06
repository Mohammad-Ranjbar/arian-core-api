<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->resource->id,
            'name'          => $this->resource->name,
            'email'         => $this->resource->email,
            'avatar'        => $this->resource->avatar,
            'mobile'        => $this->resource->mobile,
            'national_code' => $this->resource->national_code,
            'roles'         => $this->resource->roles()->get()->pluck('id')->all(),
            'makeCodes'     => $this->resource->makeCodes()->get()->pluck('id')->all(),
            'is_active'     => $this->resource->is_active,
            'locations'     => $this->resource->locations()->get()->pluck('id')->all(),
        ];
    }
}
