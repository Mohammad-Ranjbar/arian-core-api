<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleCompanyResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'folder_name' => $this->resource->folder_name,
            'site_url' => $this->resource->site_url,
            'erp_url' => $this->resource->erp_url,
            'users' => UserShortResource::collection($this->resource->users),
            'created_at' => $this->resource->created_at,
        ];
    }
}
