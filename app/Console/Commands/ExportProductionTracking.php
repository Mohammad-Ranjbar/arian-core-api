<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportProductionTracking extends Command
{
    protected $signature = 'tracking:export';

    protected $description = 'Export production_tracking data to JSON file for Filebeat';

    public function handle(): void
    {
        $filePath = storage_path('logs/production_tracking.json');

        $handle = fopen($filePath, 'w');

        DB::table('production_tracking')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {

                    $json = is_string($row->data)
                        ? $row->data
                        : json_encode($row->data);

                    fwrite($handle, $json . PHP_EOL);
                }
            });

        fclose($handle);

        $this->info('Export completed.');
    }
}
