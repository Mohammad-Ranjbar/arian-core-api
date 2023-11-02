<?php

namespace App\Services\TrackingService;

interface TrackingServiceInterface
{
    public function enter($data);

    public function exit($data);

    public function prepareData($data);
}