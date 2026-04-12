<?php

namespace App\Services;

use App\Jobs\SmsJob;
use App\Repositories\SmsServiceRepository;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;

class SmsService
{
    private SmsServiceRepository $repository;

    public function __construct(SmsServiceRepository $repository)
    {
        $this->repository = $repository;
    }

    public static function jsonSend($to, $text): PromiseInterface|Response
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . config('sms.token')
        ])->withoutVerifying()->post('https://sms.arian-dev.ir/api/service/sms', [
            'to' => $to,
            'message' => $text,
        ]);
    }

    public function send(
        $number,
        $template,
        $params = []
    ): bool
    {
        $template = $this->repository->findBy($template, 'type');
        $content = Blade::render(trim($template->getAttribute('text')), $params);
        if (app()->environment() == 'production') {

            dispatch(new SmsJob($number, $content));

        }
        return true;

    }


}
