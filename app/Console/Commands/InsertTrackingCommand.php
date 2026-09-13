<?php

namespace App\Console\Commands;

use App\Models\ProductionLine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;

class InsertTrackingCommand extends Command
{

    protected $signature = 'app:insert-tracking-command';


    protected $description = 'Command description';


    public function handle(): void
    {
        $path = public_path('final_tracking.xlsx');

        $rows = Excel::toCollection(null, $path)->first();

        $headers = $rows->shift();

        $rows->take(1)
            ->map(fn ($row) => $headers->combine($row))
            ->map(function ($row) {
                $date = Jalalian::fromFormat(
                    'Y-m-d H:i:s',
                    $row['created_at_jalali']
                )->toCarbon();

                return [
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'model_type' => ProductionLine::class,
                    'model_id' => $row['model_id'],
                    'type' => 'exit',
                    'vin' => $row['VIN'],
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            })
            ->chunk(50)
            ->each(
                fn ($chunk) => DB::table('production_tracking')->insert($chunk->toArray())
            );

    }
}
