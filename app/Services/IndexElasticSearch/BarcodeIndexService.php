<?php

namespace App\Services\IndexElasticSearch;

use App\libs\EsClient;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Exception;
use Illuminate\Support\Facades\Log;

class BarcodeIndexService
{
    /**
     *
     */
    const INDEX_NAME = 'barcodes';


    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public static function index($data): void
    {
        $client = EsClient::getInstance();
        self::remove($data['id']);
        $params = [
            'index' => self::INDEX_NAME,
            'body'  => $data
        ];
        $client->index($params);
    }


    /**
     * @throws AuthenticationException
     */
    public static function remove($id): void
    {
        $client = EsClient::getInstance();

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
            $client->deleteByQuery($params);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
        }
    }

}
