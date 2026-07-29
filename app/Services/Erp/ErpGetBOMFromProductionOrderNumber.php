<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

readonly class ErpGetBOMFromProductionOrderNumber
{
    public function __construct(private ErpOdataClient $client)
    {
    }

    public function get(string $po): array
    {
        $po = trim($po);
        $url = config('erp.alternative_list') . '?$filter=ProductionOrderNo eq \'' . $po . '\'';
        $result = $this->client->get($url);

        if (!$result) {
            Log::info('get BOM From PO: ' . $po . 'by User:' . Auth::id() . ' not found');

            throw ValidationException::withMessages(['message' => 'BOM from production order number not found']);
        }
        return $this->getResult($result);
    }


    private function getResult($result): array
    {

        return collect($result->value)->map(fn($data) => [
            'production_order_number' => $data->ProductionOrderNo,
            'variant_code' => $data->VariantCode,
            'alternate_number' => $data->AlternativeNo,
            'nom_number' => $data->ProductionBOMNo,
            'item_no' => $data->ItemNo,
            'item_description' => $data->Description,
            'quantity' => $data->Quantity,

//            'data' => $data
        ])->values()->all();
    }


}
