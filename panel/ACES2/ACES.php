<?php

namespace ACES2;

use Cache;

class ACES {

    const VERSION = '2.5.7';
    static function isNewVersion() {

        $Cache = new Cache('aces_version', 60 * 10 );
        if($Cache->isExpired()) {
            $json = json_decode(file_get_contents("https://acescript.ddns.net/v2/version.php"),1);
            $version = $json['version'];
            $Cache->saveit($version);
        } else {
            $version = $Cache->get();
        }

        if(version_compare($version, self::VERSION, '>'))
            return $version;

        return false;

    }

    static function getVersion() {

        $url = is_file(DOC_ROOT."/baces")
            ? "https://acescript.ddns.net/v2/version.php?baces"
            : "https://acescript.ddns.net/v2/version.php";

        $json = json_decode(file_get_contents($url),1);
        return $json;
    }

}