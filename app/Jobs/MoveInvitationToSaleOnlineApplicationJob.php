<?php

namespace App\Jobs;

use App\Models\SaleCompany;
use App\Services\Erp\ErpOdataClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MoveInvitationToSaleOnlineApplicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly array $data)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fileName = Storage::disk('s3')->putFile('/invitations/'.$this->data['company_id'], Storage::path($this->data['path']));
        if ($fileName) {
            $erpOdataClient = app()->make(ErpOdataClient::class);
            $company = SaleCompany::query()->find($this->data['company_id']);
            $url = $company->getAttribute('erp_url');
            $url .= '?$filter=No eq \''.$this->data['reference'].'\'';
            $data = $erpOdataClient->getOne($url);
            if ($data) {
                $temp = [
                    'reference'       => $this->data['reference'],
                    'online_order_id' => $data->Online_Contract_ID,
                    'type'            => 'customer-finance',
                    'value'           => $data->Net_To_Pay_when_issued,
                    'data'            => [
                        'file' => $fileName
                    ]
                ];
                $company->invoices()->create($temp);


                $sendData = [
                    'order_id'    => trim($data->Online_Contract_ID),
                    'expire_at'   => Carbon::make($data->Due_Date)->toDateTimeString(),
                    'amount'      => $data->Net_To_Pay_when_issued,
                    'reference'   => $data->No,
                    'description' => 'دعوت نامه شماره '.$data->No,
                    'secret'      => md5(trim($data->Online_Contract_ID).$data->Net_To_Pay_when_issued.'xyz'),
                    'file_name'   => $fileName
                ];

                Http::post($company->getAttribute('site_url'), $sendData);

                Storage::delete($this->data['path']);


            }

        }
    }
}
