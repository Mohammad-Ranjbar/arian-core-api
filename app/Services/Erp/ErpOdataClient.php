<?php

namespace App\Services\Erp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ErpOdataClient
{

    public function __construct(private readonly Client $client)
    {
    }

    public function getOne(string $url)
    {
        $result = $this->getValue($url);

        $result = (empty($result)) ? false : $result[0];
        dd($result);
        return $result;
    }

    public function getValue($url)
    {
        $content = $this->get($url);

        return $content->value ?? false;
    }

    public function get($url)
    {
        try {
            $apiRequest = $this->client->request('GET', $url, [
                'auth' => config('erp.auth'),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            return json_decode($apiRequest->getBody()->getContents());
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

}
