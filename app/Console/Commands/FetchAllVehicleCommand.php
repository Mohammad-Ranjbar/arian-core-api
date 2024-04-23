<?php

namespace App\Console\Commands;

use App\Http\Controllers\VehicleController;
use App\Jobs\SyncProductItem;
use App\Services\Erp\ErpOdataClient;
use Illuminate\Console\Command;

class FetchAllVehicleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-all-vehicle-command';

    public function __construct(private readonly ErpOdataClient $erpOdataClient,private readonly VehicleController $controller)
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = config('erp.vehicles');

        while ($url) {

            $this->info($url);

            $content = $this->erpOdataClient->get($url);

            if (isset($content->value)) {
                $bar = $this->output->createProgressBar(count($content->value));
                $bar->start();
                foreach ($content->value as $row) {
                    $this->controller->getData($row->VIN);
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
