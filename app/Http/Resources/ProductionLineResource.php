<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionLineResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int)$this->resource->id,
            'title' => $this->resource->title,
            'salon_id' => $this->resource->salon_id,
            'prefix' => $this->resource->prefix,
            'location_code' => $this->resource->location_code,
            'teams_webhook' => $this->resource->teams_webhook,
            'show_in_board' => $this->resource->show_in_board,
            'on_enter' => $this->resource->sendErp?->on_enter ?? null,
            'on_exit' => $this->resource->sendErp?->on_exit ?? null,
            'location' => $this->resource->location,
            'item_type' => $this->resource->item_type,
            'central_location' => $this->resource->central_location,
            'production_line_type' => $this->resource->production_line_type,
            'printer_address' => $this->resource->printer_address,
        ];
    }
}
