<?php

namespace App\Console\Commands;

use App\Models\Element;
use App\Models\Incident;
use App\Models\Location;
use App\Models\Vehicle;
use App\Models\VehicleIncident;
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
            $bar = $this->output->createProgressBar(count($res));

            $bar->start();
            foreach ($res['data'] as $record) {
                if (Vehicle::query()->where('vin', $record['vin'])->exists()) {
                    $element = Element::query()->firstOrCreate(['code' => $record['element_code']], ['title' => $record['element']]);
                    $incident = Incident::query()->firstOrCreate(['code' => $record['incident_code']], ['title' => $record['incident']]);

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
                }

                $bar->advance();
            }
            $url = $res['links']['next'];

            $bar->finish();
        }
    }
}
