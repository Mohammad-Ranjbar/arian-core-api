<?php

namespace App\Services;

use App\Services\Erp\ErpWsdlClient;

class SendToErpEventService
{
    public static function send($event): void
    {
        $startAt = microtime(true);
        $event->erpJob->incrementTries();
        $response = ErpWsdlClient::run(
            $event->erpJob->getAttribute('url'),
            $event->erpJob->getAttribute('method'),
            $event->erpJob->getAttribute('parameters')
        );
        $model = $event->erpJob->model;
        if ($response['status'] !== 'ok') {
            $event->erpJob->setStatusFailed($response['message']);

            if (method_exists($model, 'failedJob')) {
                $model->failedJob($response['message']);
            }
        }

        if (method_exists($model, 'successJob')) {
            $model->successJob($response['message']);
        }


        $event->erpJob->setExecutionTime($startAt, microtime(true));
        $event->erpJob->setStatusSuccess();
    }
}
