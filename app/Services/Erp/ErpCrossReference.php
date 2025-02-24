<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ErpCrossReference
{

    /**
     * @param ErpOdataClient $client
     */
    public function __construct(private readonly ErpOdataClient $client)
    {
    }


    public function get(string $reference): array
    {

        $url = config('erp.cross_reference') . '?$filter=Item_No	 eq \'' . $reference . '\'';
        $result = collect($this->client->get($url));

        return $result->pluck('Cross_Reference_No')->all();
    }
}
