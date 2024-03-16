<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductItem;
use App\Services\Erp\ErpOdataClient;
use Illuminate\Console\Command;

class SyncProductItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-product-items-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    public function __construct(private readonly ErpOdataClient $erpOdataClient)
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $url = config('erp.items');

        while ($url) {

            $this->info($url);

            $content = $this->erpOdataClient->get($url);

            if (isset($content->value)) {
                $bar = $this->output->createProgressBar(count($content->value));
                $bar->start();
                foreach ($content->value as $row) {
                    $data = [
                        'reference'     => $row->No,
                        'tracking_code' => $row->Vendor_Tracking_Code,
                        'title'         => $row->Description,
                        'description'   => $row->Description_2,
                        'type'          => $row->Engin ? 'engine' : $row->Item_Type,
                    ];
                    dispatch(new SyncProductItem($data));
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

