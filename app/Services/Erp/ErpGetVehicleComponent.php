<?php

namespace App\Services\Erp;

use App\Models\ErpProductItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpGetVehicleComponent
{

    /**
     * @param ErpOdataClient $client
     */
    public function __construct(private readonly ErpOdataClient $client)
    {
    }


    /**
     * @param $productionOrderNumber
     * @return array
     * @throws ValidationException
     */
    public function get($productionOrderNumber): array
    {
        $url = config('erp.component') . '?$filter=Prod_Order_No eq \'' . $productionOrderNumber . '\'';
        $result = $this->client->get($url);
        if (!$result) {
            Log::info('get Component: ' . $productionOrderNumber . 'by User:' . Auth::id() . ' not found');
            throw ValidationException::withMessages(['message' => 'component not found']);
        }
        return $result->value;
    }

    public function getItem($itemNo)
    {
        $url = config('erp.items') . '?$filter=No eq \'' . $itemNo . '\'';
        $row = $this->client->getOne($url);
        if (!$row) {
            return false;
        }

        $data = [
            'reference'     => $row->No,
            'tracking_code' => $row->Vendor_Tracking_Code,
            'title'         => $row->Description,
            'description'   => $row->Description_2,
            'type'          => $row->Engin ? 'engine' : $row->Item_Type,
        ];

        $record = ErpProductItem::query()->where('reference', $data['reference'])
            ->first();
        if (!$record){
            $record = new ErpProductItem($data);
        }
       // $record->setAttribute('related_to',$row->Related_Component);
        $record->save();
        return ErpProductItem::query()->create($data);

    }

}
