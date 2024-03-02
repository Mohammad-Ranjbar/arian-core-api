<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationResource extends JsonResource
{

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->resource->id,
            'title'                => $this->resource->title,
            'code'                 => $this->resource->code,
            'work_group_center_id' => $this->resource->work_group_center_id,
            'on_enter'             => $this->resource->sendErp->on_enter,
            'on_exit'              => $this->resource->sendErp->on_exit,
        ];
    }
}
