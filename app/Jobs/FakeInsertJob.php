<?php

namespace App\Jobs;


use App\libs\EsClient;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Elastic\Elasticsearch\Exception\AuthenticationException;

class FakeInsertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param $data
     */
    public function __construct(public $data)
    {

    }


    /**
     * @return void
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws AuthenticationException
     */
    public function handle(): void
    {
        $client = EsClient::getInstance();
        $client->bulk($this->data);
    }
}
