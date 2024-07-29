<?php

namespace App\Console\Commands;

use App\Models\Barcode;
use App\Services\Erp\ErpOdataClient;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncCustomerClubDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-customer-club-data-command';

    /**
     * @param  ErpOdataClient  $client
     */
    public function __construct(private readonly ErpOdataClient $client)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Barcode::query()->chunk(500, function ($items) {
            $list = $items->map(function ($item) {
                return [
                    'vin'               => $item->vin,
                    'serial_number'     => $item->code,
                    'description'       => $item->erpProductItem?->title,
                    'product_item_code' => ($item->erpProductItem) ? $item->erpProductItem->reference : '',
                ];
            })->all();
            $this->sendData([], $list);
            $this->info('500 barcode submitted');
        });
        $this->addVehicles();
    }

    private function sendData(array $records = [], array $barcodes = []): void
    {
        $this->info('Sending '.count($records));
        Http::post(config('customers.club_url'), [
            'items'    => $records,
            'barcodes' => $barcodes,
            'token'    => config('customers.club_token')
        ]);
    }

    private function addVehicles(): void
    {
        $url = config('erp.vehicles');
        do {
            $this->info($url);
            $result = $this->client->get($url);
            $list = $result->value;
            try {
                $url = $result->{'@odata.nextLink'};
            } catch (Exception $e) {
                $url = null;
            }
            $records = [];
            foreach ($list as $record) {
                if ($record->VIN and $record->Customer_No) {
                    $records[] = [
                        'vin'           => $record->VIN,
                        'customer_no'   => $record->Customer_No,
                        'navision_data' => (array) $record
                    ];
                }
                if (count($records) > 99) {
                    $this->sendData($records);
                    $records = [];
                }
            }
            if (count($records)) {
                $this->sendData($records);
            }
        } while ($url);
    }
}
