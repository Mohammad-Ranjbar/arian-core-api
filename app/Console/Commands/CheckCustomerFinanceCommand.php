<?php

namespace App\Console\Commands;

use App\Jobs\MoveInvitationToSaleOnlineApplicationJob;
use App\Models\SaleInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckCustomerFinanceCommand extends Command
{

    protected $signature = 'app:check-customer-finance-command';

    public function handle(): void
    {

        $list = [
            'CM0001485',
            'CM0001485',
            'CM0007217',
            'CM0007402',
            'CM0007551',
            'CM0006762',
            'CM0007216',
            'CM0007232',
            'CM0007240',
            'CM0007578',
            'CM0007478',
            'CM0007677',
            'CM0007258',
            'CM0007239',

        ];
        $invoices = SaleInvoice::query()->whereIn('reference', $list)->get();
        foreach ($invoices as $invoice) {
            $this->info($invoice->reference);
            dispatch(new MoveInvitationToSaleOnlineApplicationJob($invoice->id));
        }
    }
}
