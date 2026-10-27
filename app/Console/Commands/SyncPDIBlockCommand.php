<?php

namespace App\Console\Commands;

use App\Http\Controllers\BlockItemController;
use App\Jobs\CheckVehicleJob;
use App\Models\BlockItem;
use App\Models\BlockReason;
use App\Models\Vehicle;
use App\Services\Erp\ErpOdataClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

class SyncPDIBlockCommand extends Command
{

    protected $signature = 'app:sync-pdi-block-command';

    protected $description = 'Synchronize vehicle blocks based on PDI status in ERP';

    public function __construct(private readonly ErpOdataClient $erpOdataClient, public BlockItemController $blockItemController)
    {
        parent::__construct();
    }


    public function handle(): void
    {
        $startedAt = microtime(true);
        $page = 0;
        $totalRows = 0;
        $totalBlocked = 0;
        $totalErrors = 0;
        $blockedVins = [];

        $this->newLine();
        $this->components->info('PDI Block Synchronization');
        $this->line('  Fetching ERP data and checking vehicle block status');
        $this->newLine();

        $reasonId = BlockReason::query()->where('id', config('erp.pdi_block_reason_id'))->firstOrCreate([
            'title' => 'PDI BLOCK',
            'block_type' => 'critical',
        ])->getAttribute('id');

        $url = config('erp.block_pdi');

        while ($url) {

            $page++;
            $this->components->twoColumnDetail('Page', (string) $page);
            $this->line('  <fg=gray>'.OutputFormatter::escape($url).'</>');
            $this->newLine();

            $content = $this->erpOdataClient->get($url);

            if (isset($content->value)) {
                $rowCount = count($content->value);
                $totalRows += $rowCount;
                $this->components->twoColumnDetail('Rows received', (string) $rowCount);
                $bar = $this->output->createProgressBar($rowCount);
                $bar->setFormat($rowCount > 0
                    ? '  %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s%'
                    : '  No rows to process.');
                $bar->setBarCharacter('<fg=green>=</>');
                $bar->setProgressCharacter('<fg=green>></>');
                $bar->setEmptyBarCharacter('<fg=gray>·</>');
                $bar->setBarWidth(30);
                $bar->start();
                $count = 0;
                foreach ($content->value as $row) {
                    $bar->advance();

                    if ($row->PDI_Result == '' || $row->LastPDIDate == '0001-01-01' || $row->Blocked) {
                        continue;
                    }
                    if (BlockItem::query()->where('vin', $row->VIN)
                        ->where('unblocked_at','<=',now()->subDays(45))->exists())
                    {
                        continue;
                    }
                    if (!empty($row->LastPDIDate)) {
                        if (!Vehicle::query()->where('vin', $row->VIN)->exists()) {
                            CheckVehicleJob::dispatch($row->VIN);
                            continue;

                        }

                        $dateString = $row->LastPDIDate;

                        $checkDate = Carbon::parse($dateString)->diffInDays(Carbon::now()) >= 45;
//                        $blockItem = BlockItem::query()->where('vin', $row->VIN)->latest()->first();

//                        $isExpired = false;

//                        if ($blockItem) {
//                            $referenceDate = $blockItem->unblock_at;
//
//                            $isExpired = $referenceDate->lt(
//                                Carbon::now()->subDays(45)
//                            );
//                        }

                        if (($checkDate && empty($blockItem))) {
                            if ($row->AllocationDate !== "0001-01-01" || $row->PlattingDate !== "0001-01-01" || $row->LicenseNo !== "" ) {
                                continue;
                            }

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
                                $blockedVins[] = $row->VIN;
                            } catch (Exception $exception) {
                                $totalErrors++;
                                logger()->error($exception->getMessage());
                            }
                        }
                    }
                }

                $bar->finish();
                $this->newLine(2);
                $totalBlocked += $count;
                $this->components->twoColumnDetail('Blocks created on this page', (string) $count);
            } else {
                $this->components->warn('The response for this page contains no row list.');
            }
            $this->newLine();
            $url = null;
            if (isset($content->{'@odata.nextLink'})) {
                $url = $content->{'@odata.nextLink'};
            }

        }

        $this->table(['Metric', 'Result'], [
            ['Pages fetched', $page],
            ['Rows checked', $totalRows],
            ['Blocks created', $totalBlocked],
            ['Block creation errors', $totalErrors],
            ['Duration (seconds)', number_format(microtime(true) - $startedAt, 1)],
        ]);

        if ($totalErrors > 0) {
            $this->components->warn('بررسی پایان یافت؛ جزئیات Block creation errors در لاگ برنامه ثبت شده است.');
        } else {
            $this->components->info('PDI block processing completed.');
        }

        $this->newLine();
        $this->components->info('Blocked Vehicle VINs');

        if ($blockedVins === []) {
            $this->line('  No vehicles were blocked.');
        } else {
            foreach ($blockedVins as $vin) {
                $this->line('  '.OutputFormatter::escape($vin));
            }
        }

    }
}

