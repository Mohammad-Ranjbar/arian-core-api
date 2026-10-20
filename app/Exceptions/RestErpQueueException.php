<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;

class RestErpQueueException extends HttpResponseException
{
    public function __construct(int|string $jobId, ?string $message = null)
    {
        parent::__construct(response()->json([
            'error' => [
                'code' => 'ERP_QUEUE_ERROR',
                'message' => $message ?? 'درخواست ثبت شد، اما ارسال آن به صف بیزینس سنترال ناموفق بود.',
            ],
            'rest_erp_job_id' => $jobId,
        ], 503));
    }
}
