<?php

namespace App\Services\TrackingService;

interface TrackingModelInterface
{
    public function enter($data);

    public function exit($data);

    public function prepareData($data);
}