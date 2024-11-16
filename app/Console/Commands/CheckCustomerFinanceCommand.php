<?php

namespace App\Console\Commands;

use App\Models\SaleInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckCustomerFinanceCommand extends Command
{

    protected $signature = 'app:check-customer-finance-command';

    public function handle(): void
    {
        SaleInvoice::query()->where('payment_status','paid')
            ->where('erp_status','!=', 'transferred')
            ->distinct()
            ->select(['reference','sale_company_id'])
            ->get()
            ->each(function ($row) {
                if ($row->sale_company_id === 3) {
                    Http::get('https://sales.lamari.ir/api/check-customer-finance/'.$row->reference);
                }
                if ($row->sale_company_id === 2) {
                    Http::get('https://sales.arian-motor.com/api/check-customer-finance/'.$row->reference);
                }
                if ($row->sale_company_id === 1) {
                    Http::get('https://sales.arian-diesel.com/api/check-customer-finance/'.$row->reference);
                }
                $this->info($row->reference);
            });
    }
}
