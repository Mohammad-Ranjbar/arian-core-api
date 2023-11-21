<?php

namespace App\Jobs;


use App\libs\EsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FakeInsertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $data)
    {

    }

    public function handle()
    {
        $client = EsClient::getInstance();
        $client->bulk($this->data);
    }
}
