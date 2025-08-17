<?php

namespace App\Console\Commands;

use App\Models\Barcode;
use App\Models\DWVehicleServiceHistoryTree;
use App\Services\Erp\ErpOdataClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncCustomerClubDataCommand extends Command
{

    protected $signature = 'app:sync-customer-club-data-command';

    public function __construct(private readonly ErpOdataClient $client)
    {
        parent::__construct();
    }


    public function handle(): void
    {
      //  $this->addServices();
//        $this->addBarcodes();
        $this->addVehicles();
    }

    public function addServices(): void
    {
        $result = DWVehicleServiceHistoryTree::query()
            ->groupBy(['VIN', 'Document_No'])
            ->selectRaw('VIN as vin, [Document_No] as document_no ,count(*) as count, max(Date) as date')
            ->get();

        $progress = $this->output->createProgressBar($result->count());
        $progress->start();

        $sendList = [];
        foreach ($result as $row) {
            $record = [
                'service_at'   => Carbon::make($row->date)->toDateTimeString(),
                'vin'          => $row->vin,
                'reference'    => $row->document_no,
                'total_amount' => 0,
            ];
            $items = [];
            $list = DWVehicleServiceHistoryTree::query()
                ->where('VIN', $row->vin)
                ->where('Document_No', $row->document_no)
                ->get();
            foreach ($list as $item) {
                if ((int) $item->Quantity) {
                    $items[] = [
                        'title' => $item->Description.' '.$item->{'Description2'},
                        'qty'   => (int) $item->Quantity,
                    ];
                }

            }
            if (!empty($items)) {
                $record['items'] = $items;
                $sendList[] = $record;
            }

            if (count($sendList) > 99) {
                $this->sendData([], [], $sendList);
                $sendList = [];
            }

            $progress->advance();
        }

        if (count($sendList) > 1) {
            $this->sendData([], [], $sendList);
        }
        $progress->finish();
    }

    private function sendData(array $records = [], array $barcodes = [], array $services = []): void
    {
        Http::post(config('customers.club_url'), [
            'items'    => $records,
            'barcodes' => $barcodes,
            'services' => $services,
            'token'    => config('customers.club_token')
        ]);
    }

    private function addBarcodes(): void
    {
        Barcode::query()->chunk(500, function ($items) {
            $list = $items->map(function ($item) {
                return [
                    'vin'               => $item->vin,
                    'serial_number'     => $item->code,
                    'description'       => ($item->erpProductItem) ? $item->erpProductItem->title : '--',
                    'product_item_code' => ($item->erpProductItem) ? $item->erpProductItem->reference : '--',
                ];
            })->all();
            $this->sendData([], $list);
        });
    }

    private function addVehicles(): void
    {
        $url = config('erp.vehicles').'?$filter=VIN eq "N5RSU7SN5S2902863"';
        do {
            $result = $this->client->get($url);
            $list = $result->value;
            try {
                $url = $result->{'@odata.nextLink'};
            } catch (Exception $e) {
                $url = null;
            }
            $records = [];
            foreach ($list as $key => $record) {
                if ($record->VIN and $record->Customer_No) {
                    $records[] = [
                        'vin'           => $record->VIN,
                        'customer_no'   => $record->Customer_No,
                        'navision_data' => (array) $record
                    ];
                }
                if (count($records) > 50) {
                    $this->sendData($records);
                    $this->info($key);
                    $records = [];
                }
            }
            if (count($records)) {
                $this->sendData($records);
            }
        } while ($url);
    }
}
