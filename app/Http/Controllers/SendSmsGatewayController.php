<?php

namespace App\Http\Controllers;

use App\Jobs\SmsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendSmsGatewayController extends Controller
{

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->get('apiKey') != '09ceceb46308515303c3130955a31abd') {
            abort(403);
        }

        logger($request->get('to'));
        logger($request->get('content'));

        dispatch(new SmsJob(implode(',', (array) $request->get('to')), $request->get('content')));

        return response()->json(['message' => 'sms job fired']);
    }
}
