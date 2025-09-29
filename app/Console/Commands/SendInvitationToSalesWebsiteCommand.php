<?php

namespace App\Console\Commands;

use App\Models\SaleInvoice;
use App\Services\Erp\ErpOdataClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Http;

class SendInvitationToSalesWebsiteCommand extends Command
{

    protected $signature = 'app:send-invitation-to-sales-website-command';


    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        ini_set('max_execution_time', 10000);
        $today = Carbon::today();
        $erpOdataClient = app()->make(ErpOdataClient::class);
        $list = SaleInvoice::query()
            ->where('reference', 'CNIV07551')
            ->where('payment_status', 'pending')
            ->get();
        $progress = $this->output->createProgressBar($list->count());
        $progress->start();
        foreach ($list as $row) {
            $url = $row->saleCompany->getAttribute('erp_url');
            $url .= '?$filter=No eq \'' . $row->reference . '\'';
            $data = $erpOdataClient->getOne($url);

            if (Carbon::make($data->Due_Date)->greaterThan($today)) {
                $expireDate = Carbon::make($data->Due_Date);
            } elseif (Carbon::make($data->Due_Date)->addDays(15)->greaterThan($today)) {
                $expireDate = Carbon::make($data->Due_Date)->addDays(15);
            } else {
                $expireDate = Carbon::today()->addDays(15);
            }

            $sendData = [
                'order_id' => trim($data->Online_Contract_ID),
                'expire_at' => $expireDate->toDateTimeString(),
                'amount' => $data->Net_To_Pay_when_issued,
                'reference' => $data->No,
                'description' => 'دعوت نامه شماره ' . $data->No,
                'secret' => md5(trim($data->Online_Contract_ID) . $data->Net_To_Pay_when_issued . 'xyz'),
                'file_name' => $row->data['file']
            ];

            $response = Http::post($row->saleCompany->getAttribute('site_url'), $sendData);
            $this->output->info(print_r($response->json(), 1));

            $progress->advance();
        }
        $progress->finish();
    }
}
