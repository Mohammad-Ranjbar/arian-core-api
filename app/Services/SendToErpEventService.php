<?php

namespace App\Services;

use App\Services\Erp\ErpWsdlClient;
use Exception;

class SendToErpEventService
{
    public static function send($erpJob): void
    {
        try {
            $startAt = microtime(true);
            $erpJob->incrementTries();
            $erpJob->setEmptyDescription();
            $erpJob->setStatusInProgress();
            $response = ErpWsdlClient::run(
                $erpJob->getAttribute('url'),
                $erpJob->getAttribute('method'),
                $erpJob->getAttribute('parameters')
            );
            $model = $erpJob->model;
            if (empty($response)) {
                $erpJob->setStatusFailed('پاسخی از ERP دریافت نشد');
                if ($model && method_exists($model, 'failedJob')) {
                    $model->failedJob([
                        'status' => 'error',
                        'message' => 'پاسخی از ERP دریافت نشد'
                    ]);
                }
                $erpJob->setExecutionTime($startAt, microtime(true));
                return;
            }
            if ($response['status'] !== 'ok') {
                $erpJob->setStatusFailed($response['message']);

                if ($model && method_exists($model, 'failedJob')) {
                    $model->failedJob($response);
                }
                $erpJob->setExecutionTime($startAt, microtime(true));
                return;
            }

            if ($model && method_exists($model, 'successJob')) {
                $model->successJob($response);
            }
            $erpJob->setExecutionTime($startAt, microtime(true));
            $erpJob->setStatusSuccess($response);
        } catch (Exception $exception) {
            $erpJob->setStatusFailed($exception->getMessage());
            if ($model && method_exists($model, 'failedJob')) {
                $model->failedJob([
                    'status' => 'error',
                    'message' => $exception->getMessage()
                ]);
            }
            $erpJob->setExecutionTime($startAt, microtime(true));
            return;
        }

    }
}
