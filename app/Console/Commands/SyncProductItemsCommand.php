<?php

namespace App\Console\Commands;

use App\Models\ErpProductItem;
use App\Services\Erp\ErpOdataClient;
use Illuminate\Console\Command;

class SyncProductItemsCommand extends Command
{

    protected $signature = 'app:sync-product-items-command';


    public function __construct(private readonly ErpOdataClient $erpOdataClient)
    {
        parent::__construct();
    }


    public function handle(): void
    {
        $url = config('erp.items');

        while ($url) {

            $this->info($url);

            $content = $this->erpOdataClient->get($url);

            if (isset($content->value))  {

                $bar = $this->output->createProgressBar(count($content->value));
                $bar->start();
                $i = 0;
                foreach ($content->value as $key => $row) {
                    if ($row->SerialTrackingType){
                        dd($row->SerialTrackingType);
                    }

                    dd($row);


                    $data = [
                        'reference'     => $row->No,
                        'tracking_code' => $row->Vendor_Tracking_Code,
                        'title'         => $row->Description,
                        'description'   => $row->Description_2,
                        'type'          => $row->Engin ? 'engine' : $row->Item_Type,
                    ];

                   $record = ErpProductItem::query()->where('reference', $data['reference'])
                        ->first();
                   if (!$record){
                       $record = new ErpProductItem($data);
                   }
//                   $record->setAttribute('related_to',$row->Related_Component);
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

