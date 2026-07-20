<?php

namespace App\Console\Commands;

use App\Models\BOMLine;
use App\Services\Erp\ErpOdataClient;
use Illuminate\Console\Command;

class SyncBOMLinesCommand extends Command
{

    protected $signature = 'app:sync-bom-lines-command';


    public function __construct(private readonly ErpOdataClient $erpOdataClient)
    {
        parent::__construct();
    }


    public function handle(): void
    {
        $url = config('erp.bom');

        while ($url) {

            $this->info($url);

            $content = $this->erpOdataClient->get($url);

            if (isset($content->value)) {

                $bar = $this->output->createProgressBar(count($content->value));
                $bar->start();
                foreach ($content->value as $row) {

                    $data = [
                        'no' => $row->Production_BOM_No,
                        'type' => $row->Type,
                        'version' => $row->Version_Code,
                        'data' => $row

                    ];

                    $record = BOMLine::query()->where('no', $data['no'])
                        ->first();
                    if (!$record) {
                        $record = new BOMLine($data);
                    }
                    $record->save();
                    $bar->advance();
                }
                $bar->finish();
                $this->output->writeln('');
            }

            $url = null;
            if (isset($content->{'@odata.nextLink'})) {
                $url = $content->{'@odata.nextLink'};
            }

        }

    }
}

