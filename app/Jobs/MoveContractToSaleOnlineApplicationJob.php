<?php

namespace App\Jobs;

use App\Models\SaleCompany;
use App\Models\User;
use App\Notifications\UserCreateNotification;
use App\Services\Erp\ErpOdataClient;
use App\Services\SMBService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MoveContractToSaleOnlineApplicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $share;
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
        $this->share = new SMBService(
            config('erp.share_server'),
            config('erp.auth')[0],
            config('erp.auth')[1],
            config('erp.workgroups'),
            config('erp.share_folder')
        );

        $fileName = Storage::disk('s3')
            ->putFile('/contracts/'.$this->data['company_id'], Storage::path($this->data['path']));
        if ($fileName) {
            $erpOdataClient = app()->make(ErpOdataClient::class);
            $company = SaleCompany::query()->find($this->data['company_id']);
            $url = $company->getAttribute('erp_contract_url');
            $url .= '?$filter=No eq \''.$this->data['reference'].'\'';
            $data = $erpOdataClient->getOne($url);
            if ($data) {
                $sendData = [
                    'secret' => md5('A@rX4898989jsdkjsdhfxyz'),
                    'items'  => [
                        [
                            'erp_reference' => trim($data->No),
                            'erp_status'    => trim($data->Status_Code),
                            'order_id'      => trim($data->Online_Contract_ID),
                            'file_name'     => $fileName
                        ]
                    ]
                ];

                Http::post($company->getAttribute('sync_order_url'), $sendData);
                $this->share->remove($this->data['shareFile']);
                Storage::delete($this->data['path']);
            }else{
               // $user = User::query()->where('mobile', '09102322972')->first();
                $user = User::query()->where('mobile', '09107606131')->first();
                $user->notify(new UserCreateNotification($fileName));
            }

        }
    }
}
