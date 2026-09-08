<?php

namespace App\Jobs;

use App\Models\SaleCompany;
use App\Services\CustomerFinance\CustomerFinanceFactory;
use App\Services\CustomerFinance\DieselExtraPaymentService;
use App\Services\CustomerFinance\ExtraPaymentFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * @throws RequestException
     */
    public function handle(): void
    {
        $data = $this->data;
        /** @var SaleCompany $saleCompany */
        $saleCompany = SaleCompany::query()->find($data['sale_company_id']);

        if ($this->data['action'] === 'extra-payment-paid') {
            $service = ExtraPaymentFactory::make($saleCompany->getAttribute('reference'));
            $result = $service->process($this->data['online_order_id']);
            $responseData = [
                'reference' => $data['reference'],
                'order_id'  => $data['online_order_id'],
                'status'    => $result,
            ];

            $url = $saleCompany->site_url.'-response';
            if (strpos($data['online_order_id'],'_AM-',)) {
                $url = str_replace('lamari.ir','arian-motor.com',$url);
            }
            if (strpos($data['online_order_id'],'_AD-',)) {
                $url = str_replace('lamari.ir','arinadrive.com',$url);
            }

            Http::post($url, $responseData)->throw();
        }
        if ($this->data['action'] === 'customer-finance-paid') {

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

            $responseData = [
                'reference' => $data['reference'],
                'order_id'  => $data['online_order_id'],
                'status'    => $result,
            ];
            $url = $saleCompany->site_url.'-response';
            if (strpos($data['online_order_id'],'_AM-',)) {
                $url = str_replace('lamari.ir','arian-motor.com',$url);
            }
            if (strpos($data['online_order_id'],'_AD-',)) {
                $url = str_replace('lamari.ir','arinadrive.com',$url);
            }
            Http::withoutVerifying()
                ->post($url, $responseData)
                ->throw();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('jobs')->error('PublishMessageJob failed', [
            'exception' => get_class($exception),
            'message'   => $exception->getMessage(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'data'      => $this->data,
            'trace'     => $exception->getTraceAsString(),
        ]);
    }
}
