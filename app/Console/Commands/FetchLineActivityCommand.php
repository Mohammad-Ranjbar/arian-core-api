<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\ProductionLine;
use App\Models\Tracking;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchLineActivityCommand extends Command
{

    /**
     * @var string
     */
    protected $signature = 'app:fetch-line-activity-command';


    public function handle(): void
    {

        $page = 1;
        do {
            $this->info('page - '.$page);
            $url = 'http://172.16.0.52/api/in-out-histories?page='.$page;
            $res = Http::get($url)->json();

            foreach ($res as $record) {
                try {

                    $locationId = $this->mapperToProductionLine(trim($record['car_model'], '"'));
                    $location = ProductionLine::class;
                    if (!Tracking::query()->where('vin', $record['vin'])->where('type', 'exit')->exists()) {
                        if ($record['type'] == 'gate') {
                            $location = Location::class;
                            $locationId = 1;
                        }
                        $type = match ($record['type']) {
                            'in' => 'enter',
                            'out', 'gate' => 'exit',
                        };

                        $data = [
                            "data"    => "",
                            "status"  => "ok",
                            "message" => 'Consumption of VIN '.$record['vin'].' in WC  is posted.'
                        ];

                        Tracking::query()->create([
                            'vin'        => $record['vin'],
                            'type'       => $type,
                            'data'       => json_encode($data),
                            'model_type' => $location,
                            'model_id'   => $locationId,
                            'user_id'    => 1,
                            'created_at' => $record['created_at'],
                        ]);
                    }
                } catch (Exception $exception) {
                    logger($exception->getMessage());
                }

            }
            $page++;
        } while (!empty($res));
    }


    public function mapperToProductionLine($data): int
    {
        $jackItems = [
            "TN721-N60",
            "TN721-N85",
            "TN721-N90",
            "VAN-M209-14",
            "VAN-M209-16",
            "VAN-14-1401",
            "VAN-16-1401",
            "VAN-AMBULANCE-1401",
            "AMBULANCE",
            "JAC TRUCK 9TON 1401",
            "JAC TRUCK 6TON 1401",
            "JAC TRUCK8.5TON 1401",
            "JAC TRUCK 6TON 1402",
            "JAC TRUCK 8 TON 1402",
            "JAC TRUCK 9 TON 1402",
            "JSCLT01SMD",
            "JSCLT02SMD",
            "JSCLT03LMD"
        ];

        $lamaryItems = [
            "EAMA-TYPE1-1401",
            "EAMA-TYPE1-1402",
            "LAMARI EAMAX-1402",
            "LAMARI HEV-1402",
            "DSPCO010DP",
        ];
        $shakmanItems = [
            "SHACMAN-X5000-1401",
            "SHACMAN-X5000-1402",
            "SHACMAN-X5000-1403",
            "LORRY HCV-1402",
            "DUMPER HCV-1402",
            "SSCTR01TAD",
        ];
        $xgmaItems = [
            "XGMA-EXCAV22TON 1402",
            "XGMA-EXCAV36TON 1402",
            "XG836FL-1402",
            "XSHEX013MD",
        ];

        $locationId = 0;

        if (in_array($data, $jackItems)) {
            $locationId = 3;
        } elseif (in_array($data, $shakmanItems)) {
            $locationId = 4;
        } elseif (in_array($data, $xgmaItems)) {
            $locationId = 5;
        } elseif (in_array($data, $lamaryItems)) {
            $locationId = 2;
        }

        return $locationId;
    }
}
