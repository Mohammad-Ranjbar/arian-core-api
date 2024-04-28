<?php

namespace App\Console\Commands;

use App\Models\Element;
use App\Models\Incident;
use App\Models\Location;
use App\Models\Vehicle;
use App\Models\VehicleIncident;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use function PHPUnit\Framework\directoryExists;

class FetchAllFaultsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-all-faults-command';

    public function handle(): void
    {
        $this->fetchData();
        $this->readFiles();
    }

    public function fetchData()
    {
        $url = 'http://172.16.0.52/api/faults?page=715';

        $page = 1;
        while ($url) {
            $res = Http::get($url)->json();
            $this->info('page- '.$page);
            if(!empty($res)) {
                file_put_contents(dirname(__FILE__).'/list/'.uniqid().'-'.$page.'.json', json_encode($res['data']));
                $url = $res['links']['next'];
                $page++;
            }else {
                logger($url);
            }
        }
    }

    public function insertData($data) {
        $this->info(count($data));
        $insert = 0;
        foreach ($data as $record) {
            if (Vehicle::query()->where('vin', $record['vin'])->exists()) {
                $element = Element::query()->firstOrCreate(['code' => $record['element_code']], ['title' => $record['element']]);
                $incident = Incident::query()->firstOrCreate(['code' => $record['incident_code']], ['title' => $record['incident']]);
                if (!VehicleIncident::query()->where('element_id', $element->id)->where('incident_id', $incident->id)->where('vin', $record['vin'])->exists()) {
                    VehicleIncident::query()->create([
                        'user_id' => 1,
                        'type' => $record['type'],
                        'vin' => $record['vin'],
                        'element_id' => $element->getAttribute('id'),
                        'incident_id' => $incident->getAttribute('id'),
                        'description' => $record['tip'],
                        'intensity' => $record['intensity'],
                        'method' => $record['method'],
                        'created_at' => $record['created_at'],
                        'checked_at' => $record['checked_at'],
                        'custom_location_type' => Location::class,
                        'custom_location_id' => 1
                    ]);
                    $insert++;
                }

            }
        }
        $this->info('insert: '.$insert);
    }

    private function readFiles()
    {
        $files = scandir(dirname(__FILE__).'/list');
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        foreach ($files as $file) {
            if($file === '.' || $file === '..') {
                $bar->advance();
                continue;
            }
            $data = file_get_contents(dirname(__FILE__).'/list/'.$file);
            $this->insertData(json_decode($data,true));
            unlink(dirname(__FILE__).'/list/'.$file);
            $bar->advance();
        }
    }
}
