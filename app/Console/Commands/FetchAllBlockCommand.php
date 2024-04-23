<?php

namespace App\Console\Commands;

use App\Models\BlockItem;
use App\Models\Vehicle;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAllBlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-all-block-command';

    public function handle()
    {
        $url = 'http://172.16.0.52/api/blocks';

        while ($url) {
            $res = Http::get($url)->json();
            $bar = $this->output->createProgressBar(count($res));

            $bar->start();
            foreach ($res['data'] as $record) {

                try {
                    $desc = $record['description'];
                    if ($record['user']) {
                        $desc = $desc . $record['user']['name'] . ' - ';
                    }
                    if ($record['unblockedBy']) {
                        $desc = $desc . $record['unblockedBy']['name'];
                    }

                    if (Vehicle::query()->where('vin', $record['vin'])->exists()) {
                        BlockItem::query()->create([
                            'blocked_by' => 1,
                            'unblocked_by' => 1,
                            'block_reason_id' => $record['block_reason_id'],
                            'unblocked_at' => $record['unblocked_at'],
                            'description' => $desc,
                            'vin' => $record['vin'],
                            'created_at' => $record['created_at'],
                        ]);
                    }

                } catch (Exception $exception) {
                    logger($exception->getMessage());
                }


                $bar->advance();
            }
            $url = $res['links']['next'];

            $bar->finish();
        }
    }
}
