<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

readonly class ErpGetShortageBOM
{
    public function __construct(private ErpOdataClient $client)
    {
    }

    public function get(string $CarModel): array
    {
        $CarModel = trim($CarModel);

        $filter = "ItemNo eq '{$CarModel}'";

        $url = config('erp.shortage_bom') . '?$filter=' . rawurlencode($filter);

        $result = $this->client->get($url);
        dd($result);
        if (!$result) {
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
            'work_center'      => $data->RoutingLineNo,
            'line_itm_no'      => $data->LineItemNo,
            'line_itm_description'      => $data->BOMLineDescription,
            'quantity'      => $data->QuantityPer,
            'unit'      => $data->UnitMeasureCode,
            'version_code'      => $data->BOMVersionCode,
            'bom_version_status'      => $data->BOMVersionStatus,


//            'data'              => $data,
        ])->values()->all();
    }


}
