<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class InsertTrackingCommand extends Command
{

    protected $signature = 'app:insert-tracking-command';


    protected $description = 'Command description';


    public function handle(): void
    {
        $path = public_path('final_tracking.xlsx');

        $rows = Excel::toCollection(null, $path)->first();

        $headers = $rows->shift();

        $rows = $rows->map(function ($row) use ($headers) {
            return $headers->combine($row);
        });

        dd($rows->first());
    }
}
