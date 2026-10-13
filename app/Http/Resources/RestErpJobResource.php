<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestErpJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [...$this->resource->attributesToArray(), ...$this->retryFields($request)];
    }

    protected function retryFields(Request $request): array
    {
        $canRetry = in_array($this->resource->status, ['failed', 'uncertain'], true)
            && ($request->user()?->can('retry-erp-jobs') ?? false);

        return [
            'retry_url' => $canRetry ? route('rest-erp-jobs.retry', ['restErpJob' => $this->resource->getKey()]) : null,
            'retry_method' => $canRetry ? 'PUT' : null,
            'retry_requires_acknowledgement' => $this->resource->status === 'uncertain',
        ];
    }
}
