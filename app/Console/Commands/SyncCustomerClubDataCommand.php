<?php

namespace App\Console\Commands;

use App\Services\Erp\ErpOdataClient;
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
        $url = config('erp.vehicles');
        do {
            $this->info($url);
            $result = $this->client->get($url);
            $list = $result->value;
            try {
                $url = $result->{'@odata.nextLink'};
            }catch (\Exception $e){
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

    private function sendData(array $records): void
    {
        $this->info('Sending '.count($records));
        Http::post(config('customers.club_url'), [
            'items' => $records,
            'token' => config('customers.club_token')
        ]);
    }
}
