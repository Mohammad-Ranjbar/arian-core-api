<?php

namespace App\Jobs;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class FakeInsertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {

    }


    /**
     * @return bool|string
     */
    public function handle(): bool|string
    {
        try {
            $client = ClientBuilder::create()->build();
//            for ($i = 0; $i <= 200; $i++) {
                $data = random_int(1000000, 9999999);
                $params = [
                    'index' => 'fakes', 'body' => $data
                ];
                $client->index($params);
//            }

            return true;
        } catch (Exception $exception) {
            return $exception->getMessage();
        }

    }
}
