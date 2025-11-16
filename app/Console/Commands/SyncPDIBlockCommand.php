<?php

namespace App\Console\Commands;

use App\Http\Controllers\BlockItemController;
use App\Jobs\CheckVehicleJob;
use App\Models\BlockReason;
use App\Models\Vehicle;
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

        $reasonId = BlockReason::query()->where('id', config('erp.pdi_block_reason_id'))->firstOrCreate([
            'title' => 'PDI BLOCK',
            'block_type' => 'critical',
        ])->getAttribute('id');

        $url = config('erp.block_pdi');

        while ($url) {

            $this->info($url);

            $content = $this->erpOdataClient->get($url);
            dd($content);
            if (isset($content->value)) {
                $bar = $this->output->createProgressBar(count($content->value));
                $bar->start();
                $count = 0;
                foreach ($content->value as $row) {
                    if (!Vehicle::query()->where('vin', $row->VIN)->exists()) {
                        CheckVehicleJob::dispatch($row->VIN);
                        continue;

                    }

//                    $dateString = $row->Last_PDI_Date;

                    dd($row);
                    $checkDate = Carbon::parse($dateString)->diffInDays(Carbon::now()) >= 45;


                    if (!$row->Blocked && $checkDate && $row->Last_PDI_Date != '0001-01-01T00:00:00Z') {
                        $data = [
                            'block_reason_id' => $reasonId,
                            'description' => 'PDI Block',
                            'blocked_by' => 1,
                            'vin' => $row->VIN,
                            'type' => 'system'
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

