<?php

namespace App\Console\Commands;

use App\Models\SaleCompany;
use App\Models\SaleInvoice;
use App\Services\Erp\ErpOdataClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SendInvitationToSalesWebsiteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-invitation-to-sales-website-command';


    public function handle()
    {
        $today = Carbon::today();
        $erpOdataClient = app()->make(ErpOdataClient::class);
        $list = SaleInvoice::query()->where('created_at', '>', $today)->get();
        $progress = $this->output->createProgressBar($list->count());
        $progress->start();
        foreach ($list as $row) {
            $url = $row->saleCompany->getAttribute('erp_url');
            $url .= '?$filter=No eq \''.$this->data['reference'].'\'';
            $data = $erpOdataClient->getOne($url);

            if (Carbon::make($data->Due_Date)->greaterThan($today)){
                $expireDate = Carbon::make($data->Due_Date);
            }elseif(Carbon::make($data->Due_Date)->addDays(15)->greaterThan($today)){
                $expireDate = Carbon::make($data->Due_Date)->addDays(15);
            }else{
                $expireDate = Carbon::today()->addDays(15);
            }

            $sendData = [
                'order_id'    => trim($data->Online_Contract_ID),
                'expire_at'   => $expireDate->toDateTimeString(),
                'amount'      => $data->Net_To_Pay_when_issued,
                'reference'   => $data->No,
                'description' => 'دعوت نامه شماره '.$data->No,
                'secret'      => md5(trim($data->Online_Contract_ID).$data->Net_To_Pay_when_issued.'xyz'),
                'file_name'   => $row->data['file']
            ];
            dd($sendData);

            Http::post($row->saleCompany->getAttribute('site_url'), $sendData);

            $progress->advance();
        }
        $progress->finish();
    }
}
