<?php

namespace App\Console\Commands;

use App\Jobs\FakeInsertJob;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class SendFakeDataToElasticCommand extends Command
{

    protected $signature = 'insert:fake';


    protected $description = 'insert fake data to elasticsearch';


    public function handle(): void
    {
        $client = ClientBuilder::create()->build();

        for ($i=0;$i<=50;$i++){

            dispatch(new FakeInsertJob($data));
        }

        $params = [
            'index' => 'test2',
            'body'  => [ 'testField' => 'abc']
        ];

        $response = $client->index($params);


    }
}
