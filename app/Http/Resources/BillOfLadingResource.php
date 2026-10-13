<?php

namespace App\Http\Resources;

use App\Models\GatePermit;
use App\Services\Erp\RestApi\RestErpJobLinksService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillOfLadingResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'location_source_id' => $this->resource->location_source_id,
            'location_destination_id' => $this->resource->location_destination_id,
            'code' => $this->resource->code,
            'request_no' => $this->resource->request_no,
            'priority' => $this->resource->priority,
            'status' => $this->resource->status,
            'cottage' => $this->resource->cottage,
            'weight' => $this->resource->weight,
            'batch' => $this->resource->batch,
            'car_id' => $this->resource->car_id,
            'description' => $this->resource->description,
            'created_at' => $this->resource->created_at,
            'locationSource' => new LocationResource($this->resource->locationSource),
            'locationDestination' => new LocationResource($this->resource->locationDestination),
            'car' => new CarResource($this->resource->car),
            'type' => $this->resource->type,
            'items' => $this->resource->type === 'containers' ?
                $this->resource->containers()->get()->map(function ($item) {
                    return [
                        'code' => $item->code,
                        'destination' => ''
                    ];
                }) :
                $this->resource->vehicles()->get()->map(function ($item) {
                    return [
                        'code' => $item->vin,
                        'info'=>$item->data,
                        'destination' => GatePermit::withTrashed()
                            ->where('model_id',$item->vin)
                            ->where('created_at','<=',$this->resource->created_at)
                            ->latest()
                            ->first()?->location->title ?? ''
                    ];
                }),
            'actionLogs' => ActionLogResource::collection($this->resource->actionLogs),
            'actions' => $this->resource->action,
            'rest_erp_jobs' => RestErpJobSummaryResource::collection(resolve(RestErpJobLinksService::class)->forBillOfLading($this->resource)),
            'can_return' => $this->resource->canReturn()
        ];
    }
}
