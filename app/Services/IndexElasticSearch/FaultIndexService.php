<?php

namespace App\Services\IndexElasticSearch;

use Elastic\Elasticsearch\ClientBuilder;
use Exception;
use Illuminate\Support\Facades\Log;

class FaultIndexService
{
    const INDEX_NAME = 'incidents';

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