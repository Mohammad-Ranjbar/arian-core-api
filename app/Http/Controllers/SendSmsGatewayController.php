<?php

namespace App\Http\Controllers;

use App\Jobs\SmsJob;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SendSmsGatewayController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'apiKey'  => ['required'],
            'to'      => ['required', 'array'],
            'content' => ['required']
        ]);
        if ($request->get('apiKey') != '09ceceb46308515303c3130955a31abd') {
            abort(403);
        }

        dispatch(new SmsJob(implode(',',$request->get('to')),$request->get('content')));

        response()->json(['message' => 'sms job fired']);
    }
}
