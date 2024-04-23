<?php

namespace App\Console\Commands;

use App\Models\BlockItem;
use App\Models\BlockReason;
use App\Models\Element;
use App\Models\Incident;
use App\Models\Location;
use App\Models\Vehicle;
use App\Models\VehicleIncident;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAllBlockReasonCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:fetch-all-block-reason-command';


    /**
     * @return void
     */
    public function handle(): void
    {
        $url = 'http://172.16.0.52/api/reasons-list';

        while ($url) {
            $res = Http::get($url)->json();
            $bar = $this->output->createProgressBar(count($res));

            $bar->start();
            foreach ($res['data'] as $record) {
                BlockReason::query()->create($record);

                $bar->advance();
            }
            $url = $res['links']['next'];

            $bar->finish();
        }
    }
}
