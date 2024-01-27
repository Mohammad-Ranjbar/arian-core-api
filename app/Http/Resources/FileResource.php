<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->resource->uuid,
            'user_id' => $this->resource->user_id,
            'mime'    => $this->resource->mime,
            'url'     => asset($this->resource->path),
            'user'    => new UserShortResource($this->resource->user),
        ];
    }
}
