<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpGetBOMItemFromProductionBomNo
{
    public function __construct(private readonly ErpOdataClient $client)
    {
    }

    public function get(string $productionBomNo): array
    {
        //this $productionBomNo from Production_BOM_No
        $productionBomNo = trim($productionBomNo);
        $url = config('erp.bom_line') . '?$filter=Production_BOM_No eq \'' . $productionBomNo . '\'';

        $result = $this->client->get($url);
        if (!$result) {
            Log::info('get BOM From PO: ' . $productionBomNo . 'by User:' . Auth::id() . ' not found');

            throw ValidationException::withMessages(['message' => 'Bom Item from' . $productionBomNo . 'not found']);
        }
        return $this->getResult($result);
    }


    private function getResult($result): array
    {
        return collect($result->value)->map(fn($data) => [
            'Production_BOM_No' => $data->Production_BOM_No,
            'version_code'      => $data->AR_Version_Code,
            'no'                => $data->No,
            'description'       => $data->Description,
            'type'              => $data->Type,
//            'data'              => $data,
        ])->values()->all();
    }


}
