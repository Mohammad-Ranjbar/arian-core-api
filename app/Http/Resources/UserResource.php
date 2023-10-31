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
            'id'     => $this->resource->id,
            'name'   => $this->resource->name,
            'email'  => $this->resource->email,
            'avatar' => $this->resource->avatar,
            'mobile' => $this->resource->mobile,
            'roles'  => $this->resource->roles()->get()->pluck('id')->all()
        ];
    }
}
