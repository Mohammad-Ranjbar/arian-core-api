<?php

namespace App\Observers;

use App\Mail\SendStatusFailedErpJobMail;
use App\Models\ActionLog;
use App\Models\ErpJob;
use Illuminate\Support\Facades\Mail;

class ErpJobObserver
{
    public function created(ErpJob $erpJob): void
    {
        if ($erpJob->getAttribute('status') === 'failed') {
            $this->notifyUsers($erpJob);
        }
    }

    protected function notifyUsers($erpJob): void
    {

//        if (app()->environment('production')) {
//            $users = [
//                'r.pourmehr@arian-capital.com',
//                'mo.ranjbar@arian-capital.com',
////                'z.saffari@arian-capital.com',
////                'h.yadegari@arian-capital.com'
//
//            ];
//
//            foreach ($users as $userEmail) {
//                Mail::to($userEmail)->queue(new SendStatusFailedErpJobMail($erpJob));
//            }
//        }
    }

    public function updated(ErpJob $erpJob): void
    {
        if ($erpJob->getAttribute('status') === 'failed') {
            $this->notifyUsers($erpJob);
        }
    }

}
