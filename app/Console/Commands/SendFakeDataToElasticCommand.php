<?php

namespace App\Console\Commands;

use App\Jobs\FakeInsertJob;
use Illuminate\Console\Command;

class SendFakeDataToElasticCommand extends Command
{

    protected $signature = 'insert:fake';


    protected $description = 'insert fake data to elasticsearch';


    public function handle(): void
    {
        for ($i = 0; $i <= 50; $i++) {
            $data = ['a' => 123];
            FakeInsertJob::dispatch($data);
        }

    }
}
