<?php

namespace App\Jobs;

use App\Models\SaleCompany;
use App\Services\CustomerFinance\CustomerFinanceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $data)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->data['action'] === 'customer-finance-paid') {
            $data = $this->data;
            /** @var SaleCompany $saleCompany */
            $saleCompany = SaleCompany::query()->find($data['sale_company_id']);
            $saleCompany->invoices()->where('reference',$data['reference'])->update([
                'payment_status' => 'paid'
            ]);
            $service = CustomerFinanceFactory::make($saleCompany->getAttribute('reference'));
            $result = $service->process($data);
            if ($result['status'] == 'ok') {
                $saleCompany->invoices()->where('reference',$data['reference'])->update([
                    'erp_status' => 'transferred'
                ]);
            }else{
                $saleCompany->invoices()->where('reference',$data['reference'])->update([
                    'data->message' => $result['message']
                ]);
            }
        }
    }
}
