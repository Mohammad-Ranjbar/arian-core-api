<?php

namespace App\Services;

use App\Jobs\SmsJob;
use App\Repositories\SmsServiceRepository;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private SmsServiceRepository $repository;

    public function __construct(SmsServiceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function send($number, $template, $params = [])
    {
        $template = $this->repository->findBy($template, 'type');
        $content = Blade::render(trim($template->text), $params);
        if (app()->environment() == 'production') {
            return dispatch(new SmsJob($number, $content));
        }
        Log::info($number);
        Log::info($content);
        return true;

    }

    public static function sendSms($to, string $content): Response
    {
        $data = [
            'username' => config('sms.tsms.username'),
            'password' => config('sms.tsms.password'),
            'from'     => config('sms.tsms.from'),
            'to'       => $to,
            'message'  => urlencode($content)
        ];
        return Http::get(config('sms.tsms.url'), $data);
    }


}