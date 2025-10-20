<?php

namespace ACES2\IPTV;

class VideoReports
{
    const FILE_MISSING = 0;
    const URL_MISSING = 1;

    static function getStatus(int $status_code):string {

        return match($status_code) {
          0 => 'File missing.',
          1 => 'URL down.',
        };

    }
}