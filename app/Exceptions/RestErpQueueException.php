<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;

class RestErpQueueException extends HttpResponseException
{
    public function __construct(int|string $jobId)
    {
        parent::__construct(response()->json([
            'error' => [
                'code' => 'ERP_QUEUE_ERROR',
                'message' => 'درخواست ثبت شد، اما تحویل آن به صف ERP ناموفق بود.',
            ],
            'rest_erp_job_id' => $jobId,
        ], 503));
    }
}
