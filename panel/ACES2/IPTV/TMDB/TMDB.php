<?php

namespace ACES2\IPTV\TMDB;

class TMDB {

    protected $TMDB_API_KEY = "";
    protected $LANG = 'en';

    public function __construct() {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT value FROM settings WHERE name = 'iptv.videos.tmdb_api_v3' ");
        $this->TMDB_API_KEY = $r->fetch_assoc()['value'];
        if(empty($this->TMDB_API_KEY))
            throw new \Exception("Unable to get from TMDB. API key is not set");
    }

}