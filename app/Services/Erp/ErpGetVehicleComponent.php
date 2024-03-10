<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpGetVehicleComponent
{

    /**
     * @param  ErpOdataClient  $client
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
        $url = config('erp.component').'?$filter=Prod_Order_No eq \''.$productionOrderNumber.'\'';
        $result = $this->client->get($url);
        if (!$result) {
            Log::info('get Component: '.$productionOrderNumber.'by User:'.Auth::id().' not found');
            throw ValidationException::withMessages(['message' => 'component not found']);
        }
        return $result->value;
    }

}
