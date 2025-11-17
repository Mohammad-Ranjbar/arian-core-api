<?php

namespace App\Services;

use App\Jobs\SmsJob;
use App\Repositories\SmsServiceRepository;
use Illuminate\Foundation\Bus\PendingClosureDispatch;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;

class SmsService
{
    private SmsServiceRepository $repository;

    public function __construct(SmsServiceRepository $repository)
    {
        $this->repository = $repository;
    }

    public static function jsonSend($to, $text)
    {
        $username = config('sms.tsms.username');
        $password = config('sms.tsms.password');

        $mobileArray = [$to];
        $msgArray = [trim($text)];
        $smsNumberArray = [config('sms.tsms.from')];

        return Http::post(config('sms.tsms.json_url'), [
            'method'     => 'sendSms',
            'username'   => $username,
            'password'   => $password,
            'sms_number' => $smsNumberArray,
            'mobile'     => $mobileArray,
            'msg'        => $msgArray
        ])->json('rezult')[0];
    }
    public function send(
        $number,
        $template,
        $params = []
    ): bool|PendingDispatch|PendingClosureDispatch {
        $template = $this->repository->findBy($template, 'type');
        $content = Blade::render(trim($template->getAttribute('text')), $params);
        dd($content);
        if (app()->environment() == 'production') {
            return dispatch(new SmsJob($number, $content));
        }
        return true;

    }




}
