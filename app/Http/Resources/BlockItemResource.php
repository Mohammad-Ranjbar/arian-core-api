<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockItemResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'blocked_by_id'    => $this->resource->blockedBy->id,
            'blocked_by_email' => $this->resource->blockedBy->email,
            'blocked_by_name'  => $this->resource->blockedBy->name,

            'unblocked_by_id'    => $this->resource->unblockedBy ? $this->resource->unblockedBy->id : '',
            'unblocked_by_email' => $this->resource->unblockedBy ? $this->resource->unblockedBy->email : '',
            'unblocked_by_name'  => $this->resource->unblockedBy ? $this->resource->unblockedBy->name : '',

            'block_reason_title' => $this->resource->blockReason->title,
            'block_reason_id'    => $this->resource->blockReason->id,

            'unblocked_at' => $this->resource->unblocked_at,
            'description'  => $this->resource->description,
            'vin'          => $this->resource->vin,
            'makeCode'     => $this->resource->vin->makeCode->reference,
            'carModel'     => $this->resource->vin->carModel->reference,
            'created_at'   => $this->resource->created_at,
        ];
    }
}
