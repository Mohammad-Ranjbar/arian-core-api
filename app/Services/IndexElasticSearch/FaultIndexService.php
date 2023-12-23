<?php

namespace App\Services\IndexElasticSearch;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Exception;
use Illuminate\Support\Facades\Log;

class FaultIndexService
{

    const INDEX_NAME = 'incidents';

    /**
     * @param $data
     * @return void
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public static function index($data): void
    {
        $client = ClientBuilder::create()->build();
        self::remove($data['id']);
        $params = [
            'index' => self::INDEX_NAME, 'body' => $data
        ];
        $client->index($params);
    }

    /**
     * @param $id
     * @return void
     * @throws AuthenticationException
     */
    public static function remove($id): void
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
            $client->deleteByQuery($params);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
        }
    }

}