<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAllFaultsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-all-faults-command';

    public function handle()
    {
        $url = 'http://172.16.0.52/api/faults';

        while ($url) {
            $res = Http::get($url)->json();
            foreach ($res['data'] as $record) {
                // todo
            }
            $url = $res['links']['next'];

        }
    }
}
