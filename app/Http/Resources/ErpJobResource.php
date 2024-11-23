<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpJobResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'model_id' => $this->resource->model_id,
            'model_type' => $this->resource->model_type,
            'status' => $this->resource->status,
            'section' => $this->resource->section,
            'description' => $this->resource->description,
            'user_id' => $this->resource->user_id,
            'method' => $this->resource->method,
            'url' => $this->resource->url,
            'parameters' => $this->resource->parameters,
            'execute_time' => $this->resource->execute_time,
            'tries' => $this->resource->tries,
            'queue' => $this->resource->queue,
            'user' => UserResource::make($this->resource->user),
            'created_at' => $this->resource->created_at,
        ];
    }
}
