<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MicrosoftTeamsNotificationService
{
    public static function send($url, $summary, $sections = null, $themeColor = 'ff6600'): void
    {
        $data = [
            "@type" => "MessageCard",
            "@context" => "http://schema.org/extensions",
            "themeColor" => $themeColor,
            "summary" => $summary,
        ];
        if($sections) {
            $data['sections'] = self::makeSections($sections);
        }
        Http::post($url, $data);
    }

    public static function makeSections($sections): array
    {
        return array_map(function ($row) {
            return [
                "activityTitle" => $row['title'],
                "activitySubtitle" => $row['sub_title'],
                "facts" => array_map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'value' => $item['value'],
                    ];
                }, $row['items'])
            ];
        }, $sections);
    }
}
