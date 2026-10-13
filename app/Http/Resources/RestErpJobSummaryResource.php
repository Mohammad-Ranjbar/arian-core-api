<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RestErpJobSummaryResource extends RestErpJobResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'endpoint' => $this->resource->endpoint,
            'parameters' => $this->resource->parameters,
            'status' => $this->resource->status,
            'depends_on_id' => $this->resource->depends_on_id,
            'http_status' => $this->resource->http_status,
            'error_code' => $this->resource->error_code,
            'description' => $this->resource->description,
            ...$this->retryFields($request),
        ];
    }
}
