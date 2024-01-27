<?php

namespace App\libs;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;

class EsClient
{
    static Client $instance;

    /**
     * @throws AuthenticationException
     */
    public static function getInstance(): Client
    {
        if (!self::$instance) {
            self::$instance = ClientBuilder::create()->setHosts(['elasticsearch:9200'])->build();
        }
        return self::$instance;
    }
}