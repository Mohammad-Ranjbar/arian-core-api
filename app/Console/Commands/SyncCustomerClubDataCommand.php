<?php

namespace App\Console\Commands;

use App\Services\Erp\ErpOdataClient;
use Illuminate\Console\Command;

class SyncCustomerClubDataCommand extends Command
{
    /**
     * @param ErpOdataClient $client
     */
    public function __construct(private readonly ErpOdataClient $client)
    {
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-customer-club-data-command';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = config('erp.vehicles');
        $result = $this->client->get($url);
        dd($result);
    }
}
