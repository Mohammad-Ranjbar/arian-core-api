<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleInvoicesResource extends JsonResource
{

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'sale_company_id' => $this->resource->sale_company_id,
            'sale_company' => new SaleCompanyResource($this->resource->saleCompany),
            'reference' => $this->resource->reference,
            'online_order_id' => $this->resource->online_order_id,
            'payment_status' => $this->resource->payment_status,
            'erp_status' => $this->resource->erp_status,
            'data' => $this->resource->data,
            'type' => $this->resource->type,
            'value' => $this->resource->value,
            'created_at' => $this->resource->created_at,
        ];
    }
}
