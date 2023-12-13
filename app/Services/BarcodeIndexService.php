<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Exception;
use Illuminate\Support\Facades\Log;

class BarcodeIndexService
{
    const INDEX_NAME = 'barcodes';

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public static function index($data)
    {
        $client = ClientBuilder::create()->build();
        self::remove($data['id']);
        $params = [
            'index' => self::INDEX_NAME, 'body' => $data
        ];
        $response = $client->index($params);
    }

    public static function remove($id)
    {
        $client = ClientBuilder::create()->build();

        $params = [
            'index' => self::INDEX_NAME,
            'body'  => [
                'query' => [
                    'match' => [
                        'id' => $id
                    ]
                ]
            ]
        ];
        try {
            $response = $client->deleteByQuery($params);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
        }


    }

}
