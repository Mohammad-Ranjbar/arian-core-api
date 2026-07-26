<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpGetBOMFromProductionOrderNumber
{
    public function __construct(private readonly ErpOdataClient $client)
    {
    }

    public function get(string $po): array
    {
        $po = trim($po);
        $url = config('erp.alternative_list') . '?$filter=Production_Order_No eq \'' . $po . '\'';
        $result = $this->client->getOne($url);
        if (!$result) {
            Log::info('get BOM From PO: ' . $po . 'by User:' . Auth::id() . ' not found');

            throw ValidationException::withMessages(['message' => 'BOM from production order number not found']);
        }
        return $this->getResult($result);
    }


    private function getResult($result): array
    {
        return [
            'production_order_number' => $result->Production_Order_No,
            'production_order_status' => $result->Production_Order_Status,
            'production_bom_no' => $result->Production_BOM_No,
            'alternative_type' => $result->Alternative_Type,
            'alternative_no' => $result->Alternative_No,
            'modification_date' => $result->Modification_Date_Time,
            'data' => $result
        ];
    }


}
