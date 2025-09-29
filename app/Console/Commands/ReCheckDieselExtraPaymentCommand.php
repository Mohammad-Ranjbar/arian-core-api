<?php

namespace App\Console\Commands;

use App\Services\CustomerFinance\DieselExtraPaymentService;
use Illuminate\Console\Command;

class ReCheckDieselExtraPaymentCommand extends Command
{

    protected $signature = 'app:re-check-diesel-extra-payment-command {id}';



    public function handle(): void
    {
        $id = $this->argument('id');
        $service  = new DieselExtraPaymentService();
        $result = $service->process($id);
        $this->info(print_r($result,1));
    }
}
