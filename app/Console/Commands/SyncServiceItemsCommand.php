<?php

namespace App\Console\Commands;

use App\Models\ErpServiceItem;
use App\Services\Erp\ErpOdataClient;
use Illuminate\Console\Command;

class SyncServiceItemsCommand extends Command
{

    protected $signature = 'app:sync-service-items-command';


    public function __construct(private readonly ErpOdataClient $erpOdataClient)
    {
        parent::__construct();
    }


    public function handle(): void
    {
        $url = config('erp.service_items');

        while ($url) {

            $this->info($url);

            $content = $this->erpOdataClient->get($url);

            if (isset($content->value)) {
                $bar = $this->output->createProgressBar(count($content->value));
                $bar->start();
                foreach ($content->value as $row) {
                    $data = [
                        'reference' => $row->No,
                        'title' => $row->Description_2 ? $row->Description . ' ' . $row->Description_2 : $row->Description,
                        'location_title' => $row->Location_of_Service_Item,
                    ];

                    ErpServiceItem::query()->where('reference', $data['reference'])
                        ->firstOrCreate($data);
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

