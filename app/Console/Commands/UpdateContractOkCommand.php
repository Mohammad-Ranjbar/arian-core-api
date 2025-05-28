<?php

namespace App\Console\Commands;

use App\Models\SaleCompany;
use App\Services\Erp\ErpWsdlClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateContractOkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-contract-ok-command';

    public function handle()
    {
        $companies = SaleCompany::query()->whereNotNull('ok_contact_list_url')->get();
        foreach ($companies as $company) {
            $this->info('Fetching data ...');
            $list = Http::get($company->ok_contact_list_url)->json();
            $count = count($list);
            $this->info($count.' Items fetched!');
            $url = $company->ok_contact_api_url;

            $bar = $this->output->createProgressBar($count);
            $bar->start();
            foreach ($list as $code) {
                $params = [
                    'incadeaOrderNoL' => $code
                ];
                $method = 'ChangeSalesStatusToContractOK';
                $wsdlClient = new ErpWsdlClient();
                $result = $wsdlClient->run($url, $method, $params);
                if ($result['status'] != 'ok') {
                    $bar->clear();
                    $this->warn(print_r($result, 1));
                    $bar->display();
                }
                sleep(5);
                $bar->advance();
            }
            $bar->finish();
        }

    }
}
