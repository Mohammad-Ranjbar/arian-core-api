<?php

namespace App\Console\Commands;

use App\Models\SaleInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckCustomerFinanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-customer-finance-command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SaleInvoice::query()->where('payment_status','paid')
            ->where('sale_company_id',3)
            ->where('erp_status','!=', 'transferred')
            ->get()
            ->each(function ($row) {
                Http::get('https://sales.lamari.ir/api/check-customer-finance/'.$row->reference);
                $this->info($row->reference);
            });
    }
}
