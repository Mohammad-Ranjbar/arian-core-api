<?php

namespace App\Services;

use App\Services\Erp\ErpWsdlClient;

class SendToErpEventService
{
    public static function send($erpJob): void
    {
        $startAt = microtime(true);
        $erpJob->incrementTries();
        $erpJob->setEmptyDescription();
        $response = ErpWsdlClient::run(
            $erpJob->getAttribute('url'),
            $erpJob->getAttribute('method'),
            $erpJob->getAttribute('parameters')
        );
        $model = $erpJob->model;
        if ($response['status'] !== 'ok') {
            $erpJob->setStatusFailed($response['message']);
            logger('model is  in failed ');
            logger($model);
            if ($model && method_exists($model, 'failedJob')) {
                $model->failedJob($response);
            }
            $erpJob->setExecutionTime($startAt, microtime(true));
            return;
        }
        logger('model is   ');
        logger($model);
        if ($model && method_exists($model, 'successJob')) {
            $model->successJob($response);
        }
        $erpJob->setExecutionTime($startAt, microtime(true));
        $erpJob->setStatusSuccess($response);
    }
}
