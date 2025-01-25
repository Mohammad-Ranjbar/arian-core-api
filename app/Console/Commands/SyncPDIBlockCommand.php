<?php

namespace App\Console\Commands;

use App\Http\Controllers\BlockItemController;
use App\Services\Erp\ErpOdataClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class SyncPDIBlockCommand extends Command
{

    protected $signature = 'app:sync-pdi-block-command';


    public function __construct(private readonly ErpOdataClient $erpOdataClient, public BlockItemController $blockItemController)
    {
        parent::__construct();
    }


    public function handle(): void
    {
        $reasonId = config('erp.pdi_block_reason_id');
        $url = config('erp.block_pdi');

        while ($url) {

            $this->info($url);

            $content = $this->erpOdataClient->get($url);

            if (isset($content->value)) {
                $bar = $this->output->createProgressBar(count($content->value));
                $bar->start();
                $count = 0;
                foreach ($content->value as $row) {

                    $dateString = $row->Last_PDI_Date;
                    $checkDate = Carbon::parse($dateString)->diffInDays(Carbon::now()) >= 30;


                    if (!$row->Blocked && $checkDate) {
                        $data = [
                            'block_reason_id' => $reasonId,
                            'description' => 'PDI Block',
                            'blocked_by' => 1,
                            'vin' => $row->VIN
                        ];

                        try {
                            $this->blockItemController->createBlock($data);
                            $count++;
                        } catch (Exception $exception) {
                            logger($exception);
                        }

                    }
                    $bar->advance();
                }
                $bar->finish();
                $this->output->writeln('');
            }
            $this->output->writeln($count);
            $url = null;
            if (isset($content->{'@odata.nextLink'})) {
                $url = $content->{'@odata.nextLink'};
            }

        }

    }
}

