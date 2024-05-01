<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchLineActivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-line-activity-command';


    public function handle()
    {

        $page = 1;
        do{
            $this->info('page- ' . $page);
            $url = 'http://172.16.0.52/api/in-out-histories?page='.$page;
            $res = Http::get($url)->json();
            foreach ($res as $record) {
                dd($record);
            }
            $page++;
        }while(!empty($res));
    }
}
