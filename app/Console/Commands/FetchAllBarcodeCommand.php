<?php

namespace App\Console\Commands;

use App\Models\Barcode;
use App\Models\Vehicle;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class FetchAllBarcodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-all-barcode-command';

    /**
     * @return void
     */
    public function handle(): void
    {
        $url = 'http://172.16.0.52/api/barcode-list';
        Barcode::query()->where('user_id',1)->delete();
        while ($url) {
            $res = Http::get($url)->json();
            if (empty($res)) {
                logger($url);
            } else {
                $bar = $this->output->createProgressBar(count($res));

                $bar->start();
                foreach ($res['data'] as $record) {

                    try {


                        if (Vehicle::query()->where('vin', $record['vin'])->exists()) {

                            if (Barcode::query()->where('vin', $record['vin'])->where('code', $record['barcode'])->exists()) {
                                continue;
                            }
                            Barcode::query()->create([
                                'vin' => $record['vin'],
                                'user_id' => 1,
                                'code' => $record['barcode'],
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
        Artisan::call('app:sync-product-items-command');
    }

}
