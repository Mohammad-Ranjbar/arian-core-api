<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpGetShortageBOM
{
    public function __construct(private readonly ErpOdataClient $client)
    {
    }

    public function get(string $CarModel): array
    {
        $CarModel = trim($CarModel);

        $filter = "ItemNo eq '{$CarModel}' and VersionStatus eq 'Certified'";

        $url = config('erp.shortage_bom') . '?$filter=' . rawurlencode($filter);

        $result = $this->client->get($url);

        if (!$result) {
//            Log::info('get BOM From PO: ' . $CarModel . 'by User:' . Auth::id() . ' not found');

            throw ValidationException::withMessages(['message' => 'Bom Shortage Item from' . $CarModel . 'not found']);
        }
        return $this->getResult($result);
    }


    private function getResult($result): array
    {
        return collect($result->value)->map(fn($data) => [
            'item_no' => $data->ItemNo,
            'production_bom_no'      => $data->ProductionBOMNo,
            'routing_link_code'      => $data->RoutingLinkCode,
            'line_itm_no'      => $data->LineItemNo,
            'line_itm_description'      => $data->LineDescription,
            'quantity'      => $data->Quantityper,
            'unit'      => $data->UnitMeasureCode,
            'version_code'      => $data->VersionCode,
            'bom_version_status'      => $data->VersionStatus,
//            'data'              => $data,
        ])->values()->all();
    }


}
