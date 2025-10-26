<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $location = '';
        $latestLocation = $this->resource->tracking()->latest()->first();
        if ($latestLocation){
            $location = $latestLocation->model?->title ?? 'unknown last location';
        }
        return [
            'vin' => $this->resource->vin,
            'color_code' => $this->resource->color_code,
            'color_code_map' => $this->resource->colorCodeMap,
            'grade' => $this->resource->grade,
            'production_order_number' => $this->resource->production_order_number,
            'engine_no' => $this->resource->engine_no,
            'cabin_no' => $this->resource->cabin_no,
            'grade_description' => $this->resource->grade_description,
            'qc_description' => $this->resource->qc_description,
            'qc_status' => $this->resource->qc_status,
            'production_type' => $this->resource->getProductionType(),
            'is_block' => $this->resource->blockItems()->whereNull('unblocked_at')->exists(),
            'makeCode' => [
                'id' => (int)$this->resource->makeCode->id,
                'title' => $this->resource->makeCode->title,
                'reference' => $this->resource->makeCode->reference,
            ],
            'carModel' => [
                'id' => (int)$this->resource->carModel->id,
                'title' => $this->resource->carModel->title,
                'reference' => $this->resource->carModel->reference,
                'production' => $this->resource->carModel->production,
            ],
            'serial_barcodes' => config('serial_barcodes.'.$this->resource->makeCode->reference),
            'info' => $this->resource->data,
            'shortage_count' => $this->resource->shortageItems->count(),
            'shortage_done_count' => $this->resource->shortageItems->where('status','done')->count(),
            'latest_location' => $location
        ];
    }
}
