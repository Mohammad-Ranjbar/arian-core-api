<?php

namespace App\Console\Commands;

use App\Models\ErpProductItem;
use App\Services\Erp\ErpOdataClient;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncPDIBlockCommand extends Command
{

    protected $signature = 'app:sync-pdi-block-command';


    public function __construct(private readonly ErpOdataClient $erpOdataClient)
    {
        parent::__construct();
    }


    public function handle(): void
    {
        $url = config('erp.block_pdi');

        while ($url) {

            $this->info($url);

            $content = $this->erpOdataClient->get($url);

            if (isset($content->value)) {
                $bar = $this->output->createProgressBar(count($content->value));
                $bar->start();
                foreach ($content->value as $row) {

                    $dateString = $row->Last_PDI_Date;
                    $checkDate = Carbon::parse($dateString)->diffInDays() >= 30;


                    if (!$row->Blocked && $checkDate){

                    }
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

