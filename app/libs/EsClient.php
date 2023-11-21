<?php

namespace App\libs;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class EsClient
{
    static $instance;

    public static function getInstance(): Client
    {
        if (!self::$instance) {
            self::$instance = ClientBuilder::create()->build();
        }
        return self::$instance;
    }
}