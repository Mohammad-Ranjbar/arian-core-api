<?php

namespace App\Http\Resources;

use App\Models\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionLogResource extends JsonResource
{
    private const array ACTION_LABELS = [
        ActionLog::MAIN_ITEM_SHORTAGE_FORKED => 'تغییر ورژن ایتم اصلی',
    ];

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->resource->id,
            'user_id'    => $this->resource->user_id,
            'action'     => self::ACTION_LABELS[$this->resource->action] ?? $this->resource->action,
            'data'       => $this->resource->data,
            'created_at' => $this->resource->created_at,
            'user'       => new UserShortResource($this->resource->user)
        ];
    }
}
