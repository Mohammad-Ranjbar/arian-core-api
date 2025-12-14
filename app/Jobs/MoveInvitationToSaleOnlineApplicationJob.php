<?php

namespace App\Jobs;

use App\Models\SaleInvoice;
use App\Services\Erp\ErpOdataClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MoveInvitationToSaleOnlineApplicationJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $invoiceId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var SaleInvoice $invoice */
        $invoice = SaleInvoice::query()->find($this->invoiceId);


        $erpOdataClient = app()->make(ErpOdataClient::class);
        $company = $invoice->saleCompany;
        $url = $company->getAttribute('erp_url');
        $url .= '?$filter=No eq \''.$invoice->getAttribute('reference').'\'';
        $data = $erpOdataClient->getOne($url);
        if ($data) {
            $invoice->update([
                'online_order_id' => $data->Online_Contract_ID,
                'value'           => $data->Net_To_Pay_when_issued
            ]);
            $today = Carbon::today();
            if (Carbon::make($data->Due_Date)->greaterThan($today)) {
                $expireDate = Carbon::make($data->Due_Date);
            } elseif (Carbon::make($data->Due_Date)->addDays(20)->greaterThan($today)) {
                $expireDate = Carbon::make($data->Due_Date)->addDays(20);
            } else {
                $expireDate = Carbon::today()->addDays(20);
            }

            $orderId = str_replace('PARS_AM-', '', $data->Online_Contract_ID);
            $orderId = str_replace('POOYA_AM-', '', $orderId);

            $sendData = [
                'order_id'    => trim($orderId),
                'expire_at'   => $expireDate->toDateTimeString(),
                'amount'      => $data->Net_To_Pay_when_issued,
                'reference'   => $data->No,
                'description' => 'دعوت نامه شماره '.$data->No,
                'secret'      => md5(trim($orderId).$data->Net_To_Pay_when_issued.'xyz'),
                'file_name'   => $invoice->data['file']
            ];
            $url = $company->getAttribute('site_url');
            if (Str::contains($data->Online_Contract_ID,'PARS_AM')) {
                $url = 'https://sales.arian-motor.com/api/sync-invoices';
            }
            logger($url);
            logger($sendData);
            Http::post($url, $sendData);
        }else{
            logger('data not found');
        }

    }
}
