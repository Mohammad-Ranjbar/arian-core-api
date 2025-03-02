<?php

namespace App\Console\Commands;

use App\Jobs\MoveInvitationToSaleOnlineApplicationJob;
use App\Models\SaleInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckCustomerFinanceCommand extends Command
{

    protected $signature = 'app:check-customer-finance-command';

    public function handle(): void
    {
        $invoices = SaleInvoice::query()->where('sale_company_id',1)
            ->where('created_at','>', Carbon::now()->addDays(-2))
            ->where('payment_status', 'pending')
            ->get();
        $this->info(count($invoices));
        foreach ($invoices as $key => $invoice) {
            $this->info($key.': '.$invoice->reference);
            dispatch(new MoveInvitationToSaleOnlineApplicationJob($invoice->id));
        }
    }
}
