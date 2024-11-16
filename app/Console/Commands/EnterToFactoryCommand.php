<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Tracking;
use App\Models\Vehicle;
use Illuminate\Console\Command;

class EnterToFactoryCommand extends Command
{

    protected $signature = 'app:enter-to-factory';

    public function handle(): void
    {
//        $list = [
//            "N5RL42DA5RP013730", "N5RL42DA5RP013663", "N5RL42DA5RP013679", "N5RL42DA5RP013715", "N5RL42DA5RP013699",
//            "N5RL42DA5RP013705", "N5RL42DA5RP013682", "N5RL42DA5RP013702", "N5RL42DA5RP013716", "N5RL42DA5RP013703",
//            "N5RL42DA5RP013719", "N5RL42DA5RP013721", "N5RL42DA5RP013681", "N5RL42DA5RP013708", "N5RL42DA5RP013722",
//            "N5RL42DA5RP013714", "N5RL42DA5RP013713", "N5RL42DA5RP013697", "N5RL42DA5RP013744", "N5RL42DA5RP013732",
//            "N5RL42DA5RP013689", "N5RL42DA5RP013678", "N5RL42DA5RP013683", "N5RL42DA5RP013688", "N5RL42DA5RP013707",
//            "N5RL42DA5RP013742", "N5RL42DA5RP013685", "N5RL42DA5RP013692", "N5RL42DA5RP013740", "N5RL42DA5RP013694",
//            "N5RL42DA5RP013741", "N5RL42DA5RP013731", "N5RL42DA5RP013690", "N5RL42DA5RP013720", "N5RL42DA5RP013743",
//            "N5RL42DA5RP013717", "N5RL42DA5RP013734", "N5RL42DA5RP013700", "N5RL42DA5RP013739", "N5RL42DA5RP013691",
//            "N5RL42DA5RP013735", "N5RL42DA5RP013737", "N5RL42DA5RP013728", "N5RL42DA5RP013745", "N5RL42DA5RP013711",
//            "N5RL42DA5RP013725", "N5RL42DA5RP013709", "N5RL42DA5RP013686", "N5RL42DA5RP013706", "N5RL42DA5RP013695",
//            "N5RL42DA5RP013698", "N5RL42DA5RP013723", "N5RL42DA5RP013696", "N5RL42DA5RP013726", "N5RL42DA5RP013680",
//            "N5RL42DA5RP013733", "N5RL42DA5RP010393"
//        ];

        foreach ($list as $vin) {
            if(Vehicle::query()->where('vin',$vin)->count() === 0) {
                $this->info($vin);
                continue;
            }
            if (Tracking::query()->where('vin',$vin)->count() === 0) {
                Tracking::query()->create([
                    'vin' => $vin,
                    'model_type' => Location::class,
                    'model_id' => 1,
                    'user_id' => 1,
                    'type' => 'enter',
                ]);
            }
        }
    }
}
