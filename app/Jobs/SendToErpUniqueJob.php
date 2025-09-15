<?php

namespace App\Jobs;

use App\Models\ErpJob;
use App\Services\SendToErpEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendToErpUniqueJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $id)
    {
        //
    }

    public function handle(): void
    {
        SendToErpEventService::send(ErpJob::query()->find($this->id));
    }

    public function failed(Throwable $exception): void
    {
        $erpJob = ErpJob::query()->find($this->id);

        $model = $erpJob->model;

        $erpJob->setStatusFailed($exception->getMessage());
        if ($model && method_exists($model, 'failedJob')) {
            $model->failedJob([
                'status' => 'error',
                'message' => 'پاسخی از ERP دریافت نشد'
            ]);
        }
    }
}
