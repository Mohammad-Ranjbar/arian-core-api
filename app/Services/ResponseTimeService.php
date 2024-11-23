<?php

namespace App\Services;

use Icewind\SMB\BasicAuth;
use Icewind\SMB\Exception\DependencyException;
use Icewind\SMB\Exception\InvalidTypeException;
use Icewind\SMB\Exception\NotFoundException;
use Icewind\SMB\IShare;
use Icewind\SMB\ServerFactory;

class ResponseTimeService
{
    public static function getResponseTime($startTime,$endTime): string
    {
        $duration = $endTime - $startTime;
        $hours = (int)($duration / 60 / 60);
        $minutes = (int)($duration / 60) - $hours * 60;
        $seconds = $duration - $hours * 60 * 60 - $minutes * 60;
        return number_format((float)$seconds, 4, '.', '');
    }
}
