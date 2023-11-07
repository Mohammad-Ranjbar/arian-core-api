<?php

namespace App\Services;

use App\Repositories\SmsServiceRepository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class SmsService
{
    use HttpServiceTrait;

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
            return $this->sendSms($number, $content);
        }
        Log::info($number);
        Log::info($content);
        return true;
    }


}