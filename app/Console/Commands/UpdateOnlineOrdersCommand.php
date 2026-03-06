<?php

namespace App\Console\Commands;

use App\Models\SaleCompany;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UpdateOnlineOrdersCommand extends Command
{

    protected $signature = 'app:update-online-orders-command';

    public function handle(): void
    {
        $companies = SaleCompany::query()->get();
        foreach ($companies as $company) {
            $url = $company->getAttribute('erp_contract_url');
            while ($url) {
                $this->info($url);
                $content = null;
                try {
                    $content = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json'
                    ])->withOptions([
                        'auth'    => config('erp.auth'),
                    ])->get($url)->json();
                }
                catch (Exception $exception) {
                    $this->warn($exception->getMessage());
                    $this->warn($url);
                }

                if (isset($content['value'])) {
                    $bar = $this->output->createProgressBar(count($content['value']));
                    $bar->start();
                    $list = [];
                    $parsList = [];
                    $arinaList = [];
                    foreach ($content['value'] as $item) {
                        $row = (object) $item;
                        if ($row->Online_Contract_ID) {
                            $z = [
                                'erp_reference' => $row->No,
                                'erp_status'    => $row->Status_Code,
                                'order_id'      => $row->Online_Contract_ID
                            ];
                            if (isset($row->VIN)) {
                                $z['vin'] = $row->VIN;
                            }
                            if (Str::contains($row->Online_Contract_ID,'PARS_AM')) {
                                $parsList[] = $z;
                            }elseif (Str::contains($row->Online_Contract_ID,'PARS_AD')) {
                                $arinaList[] = $z;
                            }else{
                                $list[] = $z;
                            }
                        }
                        $bar->advance();
                    }
                    try {
                        if (count($list)) {
                            Http::withoutVerifying()->post($company->sync_order_url, [
                                'items'  => $list,
                                'secret' => md5('A@rX4898989jsdkjsdhfxyz'),
                            ]);
                        }
                        if (count($parsList)) {
                            $parsUrl = 'https://sales.arian-motor.com/api/sync-orders-erp' ;
                            Http::withoutVerifying()->post($parsUrl, [
                                'items'  => $parsList,
                                'secret' => md5('A@rX4898989jsdkjsdhfxyz'),
                            ]);
                        }
                        if (count($arinaList)) {
                            $parsUrl = 'https://sales.arinadrive.com/api/sync-orders-erp' ;
                            Http::withoutVerifying()->post($parsUrl, [
                                'items'  => $arinaList,
                                'secret' => md5('A@rX4898989jsdkjsdhfxyz'),
                            ]);
                        }
                    }catch (Exception $exception) {
                        $this->error($exception->getMessage());
                    }

                    $bar->finish();
                }

                $url = null;
                if (isset($content['odata.nextLink'])) {
                    $url = $content['odata.nextLink'];
                }
            }
        }
    }
}
