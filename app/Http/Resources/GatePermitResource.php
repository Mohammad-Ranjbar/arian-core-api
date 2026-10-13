<?php

namespace App\Http\Resources;

use App\Services\Erp\RestApi\RestErpJobLinksService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GatePermitResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type,
            'location_id' => $this->resource->location_id,
            'location' => new LocationResource($this->resource->location),
            'model_type' => $this->resource->model?->getTable() ?? 'unknown',
            'model_id' => $this->resource->model->id ? $this->resource->model->id : $this->resource->model->vin,
            'model' => $this->resource->model,
            'source_id' => $this->resource->source_id,
            'source' => LocationResource::make($this->resource->source),
            'billOfLading' => $this->resource->BillOfLadingItem?->billOfLading ,
            'deleted_at' => $this->resource->deleted_at,
            'created_at' => $this->resource->created_at,
            'rest_erp_jobs' => RestErpJobSummaryResource::collection(resolve(RestErpJobLinksService::class)->forGatePermit($this->resource)),
        ];
    }
}
