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
            'CNIV07879',
            'CNIV07882',
            'CNIV07886',
            'CNIV07919',
            'CNIV07927',
            'CNIV07930',
            'CNIV07933',
            'CNIV07943',
            'CNIV07953',
            'CNIV07994',
            'CNIV08023',
            'CNIV08027',
        ];
        $this->info(count($list));
        $invoices = SaleInvoice::query()->whereIn('reference', $list)->get();
        foreach ($invoices as $key => $invoice) {
            $this->info($key.': '.$invoice->reference);
            dispatch(new MoveInvitationToSaleOnlineApplicationJob($invoice->id));
        }
    }
}
