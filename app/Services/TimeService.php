<?php

namespace App\Services;

use Carbon\Carbon;

class TimeService
{
    public static function getDiffTwoTime(Carbon $startTime, Carbon $endTime): string
    {
        $hours = $startTime->diffInRealHours($endTime);
        $minutes = $startTime->diff($endTime)->format('%I');
        return sprintf('%02d', $hours) . ':' . $minutes;
    }

    public static function getSumTwoTime(string $startTime, string $endTime): string
    {
        list($hStart, $mStart) = explode(':', $startTime);
        list($hEnd, $mEnd) = explode(':', $endTime);


        $totalMinutes = intval($mStart) + intval($mEnd);
        $totalHours = intval($hStart) + intval($hEnd);

        if ($totalMinutes >= 60) {
            $totalMinutes -= 60;
            $totalHours++;
        }

        return sprintf('%02d',$totalHours) . ':' . sprintf('%02d',$totalMinutes);
    }
}

