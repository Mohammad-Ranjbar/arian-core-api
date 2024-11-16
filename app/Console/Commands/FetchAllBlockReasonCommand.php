<?php

namespace App\Console\Commands;

use App\Models\BlockReason;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAllBlockReasonCommand extends Command
{

    protected $signature = 'app:fetch-all-block-reason-command';


    public function handle(): void
    {
//        $url = 'http://172.16.0.52/api/reasons-list';

        while ($url) {
            $res = Http::get($url)->json();
            $bar = $this->output->createProgressBar(count($res));

            $bar->start();
            foreach ($res['data'] as $record) {
                if (BlockReason::query()->where('id', $record['id'])->exists()) {
                    continue;
                }
                BlockReason::query()->create($record);

                $bar->advance();
            }
            $url = $res['links']['next'];

            $bar->finish();
        }
    }
}
