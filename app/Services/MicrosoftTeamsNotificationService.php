<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MicrosoftTeamsNotificationService
{
    public static function MessageCard($url, $title, $summary, $sections = null, $themeColor = 'ff6600'): void
    {
        $data = [
            "@type"      => "MessageCard",
            "@context"   => "https://schema.org/extensions",
            "themeColor" => $themeColor,
            "title"      => $title,
            "summary"    => $summary,
            "sections"   => $sections,
        ];

        Http::post($url, $data);
    }


}
