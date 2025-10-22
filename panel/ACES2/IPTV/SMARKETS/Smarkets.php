<?php

namespace ACES2\IPTV\SMARKETS;

class Smarkets {

    private const URL = "https://api.smarkets.com/v3/";

    private const EVENT_TYPES = array(
        'mlb' => 'baseball_match',
        'baseball' => 'baseball_match',
        'nfl' => 'american_football_match',
        'nba' => 'basketball_match'
    );

    private const EVENT_FILTER = array( //SINCE I IS NOT POSSIBLE TO FILTER LEAGUE ON SMARKET WE WILL FIND THIS STRING ON FULL SLAG
        'mlb' => '/sport/baseball/mlb',
        'baseball' => '/sport/baseball',
        'nfl' => '/sport/american-football/nfl/',
        'nba' => '/sport/basketball/nba/'
    );

    static public function getEvents(
        string $event_type = null,
        string $start_datetime_from = null,
        string $start_datetime_to = null,
        int $limit = 30 ):array {

        $event_type = strtolower($event_type);

        $url = self::URL . "events/?" ;
        if(!is_null($event_type))
            $url .= "type=" . self::EVENT_TYPES[$event_type];

        $start_datetime_from = is_null($start_datetime_from)
            ? date("Y-m-d") : $start_datetime_from;

        $curl = new \ACES2\Curl($url. "&sort=display_order,start_datetime,name&limit=$limit");
        $json = json_decode($curl->getBody(), true);

        $games = [];
        foreach($json['events'] as $event) {
            if(strpos($event['full_slug'],
                self::EVENT_FILTER[$event_type]) !== false )
                $games[] = new Event($event, $event_type);
        }

        return $games;
    }




}