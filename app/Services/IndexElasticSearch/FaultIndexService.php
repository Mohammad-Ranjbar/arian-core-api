<?php

namespace App\Services\IndexElasticSearch;

use App\libs\EsClient;
use Exception;
use Illuminate\Support\Facades\Log;

class FaultIndexService
{

    const INDEX_NAME = 'incidents';


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